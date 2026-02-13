<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\Customer;
use App\Models\OrderLog;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'reseller', 'customer', 'items', 'courier']); 

        // 1. Search (Order Number, Customer Name, Mobile)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('mobile', 'like', "%{$search}%");
                  })
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Order Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter by Call Status
        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }

        // 4. Filter by Courier
        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        // 5. Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        // 6. Payment Method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $couriers = Courier::all();

        return view('orders.index', compact('orders', 'couriers'));
    }

    /**
     * Show general order create form.
     */
    public function create()
    {
        // Predict next order number for UI
        $dateStr = date('Ymd');
        $latestOrder = Order::whereDate('created_at', today())->latest()->first();
        $sequence = 1;
        if ($latestOrder) {
            $parts = explode('-', $latestOrder->order_number);
            if (count($parts) === 3 && $parts[1] === $dateStr) {
                $sequence = intval($parts[2]) + 1;
            }
        }
        $nextOrderNumber = 'ORD-' . $dateStr . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        $couriers = Courier::all();
        $slData = config('locations.sri_lanka');

        return view('orders.create', compact('nextOrderNumber', 'couriers', 'slData'));
    }

    /**
     * API: Search Products for Order Form
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        
        $products = Product::with(['variants.unit'])
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orWhereHas('variants', function ($q) use ($query) {
                $q->where('sku', 'like', "%{$query}%");
            })
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

            // Fulfillment & Address
            'courier_id' => 'nullable|exists:couriers,id',
            'courier_charge' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'call_status' => 'nullable|string',
            'sales_note' => 'nullable|string',
            'order_status' => 'nullable|string',
            'customer.city' => 'nullable|string',
            'customer.district' => 'nullable|string',
            'customer.province' => 'nullable|string',
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

            $order->status = $validated['order_status'] ?? 'pending';
            
            // New Fields
            $order->courier_id = $validated['courier_id'] ?? null;
            $order->courier_charge = $validated['courier_charge'] ?? 0;
            $order->payment_method = $validated['payment_method'] ?? 'COD';
            $order->call_status = $validated['call_status'] ?? 'pending';
            $order->sales_note = $validated['sales_note'] ?? null;
            
            // Capture Address Snapshot
            $order->customer_city = $validated['customer']['city'] ?? null;
            $order->customer_district = $validated['customer']['district'] ?? null;
            $order->customer_province = $validated['customer']['province'] ?? null;
            
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
                    'total_price' => $subtotal,
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
            $order->total_amount = $totalAmount + $order->courier_charge;
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
        $dateStr = date('Ymd');
        $latestOrder = Order::whereDate('created_at', today())->latest()->first();
        
        $sequence = 1;
        if ($latestOrder) {
             $parts = explode('-', $latestOrder->order_number);
             if (count($parts) === 3 && $parts[1] === $dateStr) {
                 $sequence = intval($parts[2]) + 1;
             }
        }

        do {
            $number = 'ORD-' . $dateStr . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = Order::where('order_number', $number)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);
        
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
     * Display the specified order (Invoice View).
     */
    public function show(Order $order)
    {
        $order->load(['items.variant', 'customer', 'reseller', 'user']);
        return view('orders.show', compact('order'));
    }

    /**
     * Download the order as PDF.
     */
    public function downloadPdf(Order $order)
    {
        $order->load(['items.variant', 'customer', 'reseller', 'user']);
        $pdf = Pdf::loadView('orders.pdf', compact('order'));
        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        $order->load(['items.variant', 'customer', 'reseller']);
        $couriers = Courier::all();
        $slData = config('locations.sri_lanka');
        
        return view('orders.edit', [
            'order' => $order,
            'orderFull' => $order,
            'couriers' => $couriers,
            'slData' => $slData
        ]);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
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
            'customer.city' => 'nullable|string',
            
            // Products
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',

            // Fulfillment & Address
            'courier_id' => 'nullable|exists:couriers,id',
            'courier_charge' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'call_status' => 'nullable|string',
            'sales_note' => 'nullable|string',
            'order_status' => 'nullable|string',
            'customer.city' => 'nullable|string',
            'customer.district' => 'nullable|string',
            'customer.province' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // 1. Revert Stock for OLD items
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::find($item->product_variant_id);
                    if ($variant) {
                        $variant->increment('quantity', $item->quantity);
                    }
                }
            }
            
            // 2. Clear OLD items
            $order->items()->delete();

            // 3. Update Customer & Order Details
            $customer = Customer::updateOrCreate(
                ['mobile' => $validated['customer']['mobile']],
                [
                    'name' => $validated['customer']['name'],
                    'landline' => $validated['customer']['landline'] ?? null,
                    'address' => $validated['customer']['address'],
                    'city' => $validated['customer']['city'] ?? null,
                    'country' => 'Sri Lanka',
                ]
            );

            $order->order_date = $validated['order_date'];
            $order->order_type = $validated['order_type'];
            $order->reseller_id = $validated['order_type'] === 'reseller' ? $validated['reseller_id'] : null;
            $order->customer_id = $customer->id;
            // Legacy fields update
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->mobile;
            $order->customer_address = $customer->address;
            
             // Create/Update Logic for New Fields
            $order->status = $validated['order_status'] ?? $order->status;
            $order->courier_id = $validated['courier_id'] ?? null;
            $order->courier_charge = $validated['courier_charge'] ?? 0;
            $order->payment_method = $validated['payment_method'] ?? 'COD';
            $order->call_status = $validated['call_status'] ?? 'pending';
            $order->sales_note = $validated['sales_note'] ?? null;
             // Capture Address Snapshot
            $order->customer_city = $validated['customer']['city'] ?? null;
            $order->customer_district = $validated['customer']['district'] ?? null;
            $order->customer_province = $validated['customer']['province'] ?? null;

            $order->save();

            $totalAmount = 0;
            $totalCost = 0;
            $totalCommission = 0;

            // 4. Process NEW Items
            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::with('product')->find($itemData['id']);
                
                // Stock Check
                if ($variant->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$variant->product->name} (SKU: {$variant->sku})");
                }
                
                // Limit Price Check
                if ($itemData['selling_price'] < $variant->limit_price) {
                     throw new \Exception("Selling price for {$variant->product->name} (SKU: {$variant->sku}) cannot be lower than limit price ({$variant->limit_price})");
                }

                $qty = $itemData['quantity'];
                $unitPrice = $itemData['selling_price'];
                $basePrice = $variant->limit_price;
                $subtotal = $unitPrice * $qty;
                
                $itemCost = $basePrice * $qty;
                $itemCommission = ($unitPrice - $basePrice) * $qty;

                // Create Item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'base_price' => $basePrice, 
                    'total_price' => $subtotal,
                    'subtotal' => $subtotal,
                ]);

                // Deduct Stock
                $variant->decrement('quantity', $qty);

                // Accumulate totals
                $totalAmount += $subtotal;
                $totalCost += $itemCost;
                
                if ($order->order_type === 'reseller') {
                    $totalCommission += $itemCommission;
                }
            }

            // 5. Update Order Totals
            $order->total_amount = $totalAmount + $order->courier_charge;
            $order->total_cost = $totalCost;
            $order->total_commission = $totalCommission;
            $order->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully!',
                'redirect' => route('orders.index'),
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
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {
            // Restore Stock
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::find($item->product_variant_id);
                    if ($variant) {
                        $variant->increment('quantity', $item->quantity);
                    }
                }
            }

            // Delete Order (Cascades items)
            $order->delete();

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Order deleted successfully and stock restored.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete order: ' . $e->getMessage());
        }
    }
    /**
     * Display the Call List for orders.
     */
    public function callList(Request $request)
    {
        $query = Order::with(['customer', 'reseller', 'items']);
        
        // Default to 'pending' call status if not specified, 
        // OR user might want to see all. Let's start with all but maybe sort by pending.
        // Actually, user said "Focused and filtering on call status".
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('orders.call_list', compact('orders'));
    }

    /**
     * Update Order or Call Status via AJAX.
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'nullable|in:pending,hold,confirm,shipped,delivered,cancelled',
            'call_status' => 'nullable|in:pending,confirm,cancel',
            'sales_note' => 'nullable|string',
        ]);

        if (array_key_exists('status', $validated)) {
            $order->status = $validated['status'];
        }
        
        if (array_key_exists('call_status', $validated)) {
            $order->call_status = $validated['call_status'];
        }
        
        if (array_key_exists('sales_note', $validated)) {
             $order->sales_note = $validated['sales_note'];
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
            'call_status' => $order->call_status,
            'status' => $order->status
        ]);
    }
}
