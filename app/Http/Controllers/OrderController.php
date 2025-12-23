<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders (Order List).
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'reseller', 'items', 'city'])->latest();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(20);
        return view('orders.index', compact('orders'));
    }
    
    /**
     * Display Call List (Pending/On Hold orders needing confirmation).
     */
    public function callList()
    {
        $orders = Order::whereIn('status', ['pending', 'on_hold'])
                       ->orderBy('created_at', 'asc')
                       ->paginate(20);
        return view('orders.call_list', compact('orders'));
    }

    /**
     * Show general order create form.
     */
    public function create()
    {
        $products = Product::select('id', 'name', 'sku', 'selling_price', 'quantity')->get();
        $cities = \App\Models\City::all();
        return view('orders.create', compact('products', 'cities'));
    }

    /**
     * Store a new order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'customer_address' => 'required|string',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'sales_note' => 'nullable|string',
            'order_type' => 'nullable|string', // 'reseller' or 'general'
            'reseller_id' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // Generate Order ID
            $orderNumber = $this->generateOrderNumber();
            
            $order = new Order();
            $order->order_number = $orderNumber;
            $order->user_id = Auth::id();
            $order->reseller_id = $validated['reseller_id'] ?? null;
            $order->customer_name = $validated['customer_name'];
            $order->customer_phone = $validated['customer_phone'];
            $order->customer_address = $validated['customer_address'];
            $order->city_id = $validated['city_id'];
            $order->status = 'pending';
            $order->payment_method = $validated['payment_method'];
            $order->sales_note = $validated['sales_note'];
            
            // Calculate total first (or sum up items)
            $totalAmount = 0;
            $order->total_amount = 0; // Temp
            $order->save();
            
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['id']);
                $itemTotal = $product->selling_price * $productData['quantity'];
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $productData['quantity'],
                    'unit_price' => $product->selling_price,
                    'total_price' => $itemTotal,
                ]);
                
                $totalAmount += $itemTotal;
            }
            
            $order->total_amount = $totalAmount;
            $order->save();
            
            // Log creation
            $this->logAction($order->id, 'created', 'Order created successfully.');

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Order created successfully: ' . $orderNumber);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating order: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique order number.
     */
    private function generateOrderNumber()
    {
        do {
            $number = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $number)->exists());
        
        return $number;
    }
    
    /**
     * Log action helper.
     */
    private function logAction($orderId, $action, $description = null)
    {
        OrderLog::create([
            'order_id' => $orderId,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
    
    /**
     * Update Status (e.g., confirm, cancel).
     */
    public function updateStatus(Request $request, $id, \App\Services\StockService $stockService)
    {
        $order = Order::with('items.product')->findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->input('status');
        
        // Stock Deduction Logic
        if ($newStatus === 'dispatched' && $oldStatus !== 'dispatched' && $oldStatus !== 'delivered') {
            DB::beginTransaction();
            try {
                foreach ($order->items as $item) {
                     // Deduct using FIFO and get cost
                     $costPrice = $stockService->deductStock($item->product, $item->quantity);
                     
                     // Update item with cost snapshot
                     $item->cost_price = $costPrice;
                     $item->save();
                }
                
                $order->status = $newStatus;
                $order->dispatched_at = now();
                $order->save();
                
                $this->logAction($order->id, 'dispatched', "Order dispatched and stock deducted.");
                
                DB::commit();
                return back()->with('success', 'Order dispatched and stock updated.');
                
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error dispatching order: ' . $e->getMessage());
            }
        }
        
        // Basic Status Update for other statuses
        $order->status = $newStatus;
        if ($newStatus === 'delivered') $order->delivered_at = now();
        if ($newStatus === 'cancelled') $order->cancelled_at = now();
        // If cancelling a dispatched order, we should theoretically add stock back. 
        // For simplicity/safety, let's manual restock or handle via "Return" flow only.
        
        $order->save();
        $this->logAction($order->id, 'status_updated', "Status changed to {$newStatus}");
        
        return back()->with('success', 'Order status updated.');
    }
}
