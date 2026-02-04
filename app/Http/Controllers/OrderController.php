<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\Customer;
use App\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'reseller', 'customer', 'items', 'city'])->latest();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                  });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->input('order_type'));
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('order_date', [$request->input('date_from'), $request->input('date_to')]);
        }

        $orders = $query->paginate(20);
        return view('orders.index', compact('orders'));
    }

    /**
     * Show general order create form.
     */
    public function create()
    {
        return view('orders.create');
    }

    /**
     * API: Search Products for Order Form
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        
        $products = Product::with(['variants.unit'])
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%") // Optional: Search description too
            ->limit(20)
            ->get();
            
        // Flatten variants for easier frontend consumption
        $results = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                // Determine display name (e.g., "Product Name - XL / Red")
                $variantName = $product->name;
                if ($variant->unit && $variant->unit_value) {
                    $variantName .= " (" . $variant->unit_value . " " . $variant->unit->short_name . ")";
                }

                $results[] = [
                    'id' => $variant->id,
                    'name' => $variantName,
                    'image' => $variant->image ?? $product->image,
                    'sku' => $variant->sku,
                    'selling_price' => $variant->selling_price,
                    'limit_price' => $variant->limit_price,
                    'stock' => $variant->quantity,
                    'unit' => $variant->unit->short_name ?? '',
                ];
            }
        }
        
        return response()->json($results);
    }

    /**
     * API: Search Resellers for Order Form
     */
    public function searchResellers(Request $request)
    {
        $query = $request->get('q');
        
        $resellers = Reseller::where('name', 'like', "%{$query}%")
            ->orWhere('business_name', 'like', "%{$query}%")
            ->orWhere('mobile', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'business_name', 'mobile']);
            
        return response()->json($resellers);
    }

    /**
     * Store a new order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:reseller,direct',
            'order_date' => 'required|date',
            'reseller_id' => 'required_if:order_type,reseller|nullable|exists:resellers,id',
            
            // Customer Details
            'customer.name' => 'required|string|max:255',
            'customer.mobile' => 'required|string|max:20',
            'customer.landline' => 'nullable|string|max:20',
            'customer.address' => 'required|string',
            'customer.city' => 'nullable|string', // Optional if we just store address string
            
            // Products
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create or Update Customer
            // Strategy: Search by mobile number, if exists update, else create
            // Note: User logic "customer we specify" implies we can create new on the fly.
            $customer = Customer::updateOrCreate(
                ['mobile' => $validated['customer']['mobile']],
                [
                    'name' => $validated['customer']['name'],
                    'landline' => $validated['customer']['landline'] ?? null,
                    'address' => $validated['customer']['address'],
                    'city' => $validated['customer']['city'] ?? null, // Assuming mixed use or text field
                    // Add other fields if strictly required by schema (e.g. country default)
                    'country' => 'Sri Lanka', // Default
                ]
            );

            // 2. Create Order
            $orderNumber = $this->generateOrderNumber();
            
            $order = new Order();
            $order->order_number = $orderNumber;
            $order->order_date = $validated['order_date'];
            $order->order_type = $validated['order_type'];
            $order->user_id = Auth::id(); // Admin creating the order
            $order->reseller_id = $validated['order_type'] === 'reseller' ? $validated['reseller_id'] : null;
            $order->customer_id = $customer->id;
            
            // Fallback legacy fields (optional, but good for redundancy if migrated)
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->mobile;
            $order->customer_address = $customer->address;

            $order->status = 'pending';
            $order->save();

            $totalAmount = 0;
            $totalCost = 0;
            $totalCommission = 0;

            // 3. Process Items
            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::with('product')->find($itemData['id']);
                
                // Stock Validation (Optional: Validation rule could handle this, but explicit check is safer)
                if ($variant->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$variant->product->name} (SKU: {$variant->sku})");
                }
                
                // Limit Price Validation
                if ($itemData['selling_price'] < $variant->limit_price) {
                     throw new \Exception("Selling price for {$variant->product->name} (SKU: {$variant->sku}) cannot be lower than limit price ({$variant->limit_price})");
                }

                $qty = $itemData['quantity'];
                $unitPrice = $itemData['selling_price'];
                $basePrice = $variant->limit_price; // Assuming limit_price IS the base/cost price for commission calc
                $subtotal = $unitPrice * $qty;
                
                $itemCost = $basePrice * $qty;
                $itemCommission = ($unitPrice - $basePrice) * $qty;

                // Create Order Item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name, // Snapshot
                    'sku' => $variant->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'base_price' => $basePrice,
                    'subtotal' => $subtotal,
                ]);

                // Deduct Stock
                $variant->decrement('quantity', $qty);

                // Accumulate Totals
                $totalAmount += $subtotal;
                $totalCost += $itemCost;
                
                // Commission only applies for Reseller orders
                if ($order->order_type === 'reseller') {
                    $totalCommission += $itemCommission;
                }
            }

            // 4. Update Order Totals
            $order->total_amount = $totalAmount;
            $order->total_cost = $totalCost;
            $order->total_commission = $totalCommission;
            $order->save();

            // 5. Log Action
            $this->logAction($order->id, 'created', 'Order created successfully.');

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully!',
                'redirect' => route('orders.index'),
                'order_number' => $orderNumber
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
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
}
