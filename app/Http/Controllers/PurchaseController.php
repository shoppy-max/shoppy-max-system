<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases.
     */
    public function index(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'user', 'items'])->latest()->paginate(20);
        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::select('id', 'name', 'sku')->get();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    /**
     * Store a newly created purchase in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'nullable', // Can be null if creating new
            'items.*.product_name' => 'nullable|string|max:255', // Required if product_id is null
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchasing_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $purchase = new Purchase();
            $purchase->purchasing_number = $this->generatePurchasingNumber();
            $purchase->supplier_id = $validated['supplier_id'];
            $purchase->user_id = Auth::id();
            $purchase->status = 'pending';
            
            // Calculate Total & Prepare Items
            $totalAmount = 0;
            $purchaseItemsData = [];

            foreach ($validated['items'] as $item) {
                // Determine Product ID (Existing or New)
                $productId = $item['product_id'] ?? null;
                $productName = $item['product_name'] ?? null;

                if (!$productId && $productName) {
                    // Create Draft Product
                    $newProduct = Product::create([
                        'name' => $productName,
                        'sku' => 'SKU-' . strtoupper(Str::random(10)), // Temp SKU
                        'selling_price' => 0, // Default for draft
                        'category_id' => null, // Nullable now
                        'quantity' => 0,
                    ]);
                    $productId = $newProduct->id;
                } elseif (!$productId) {
                    throw new \Exception("Product ID or Name is required for all items.");
                }

                $lineTotal = $item['quantity'] * $item['purchasing_price'];
                $totalAmount += $lineTotal;

                $purchaseItemsData[] = [
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'purchasing_price' => $item['purchasing_price'],
                    'total_price' => $lineTotal,
                ];
            }

            $purchase->total_amount = $totalAmount;
            $purchase->save();

            $purchase->save();

            // Save Items
            foreach ($purchaseItemsData as $data) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity'],
                    'purchasing_price' => $data['purchasing_price'],
                    'total_price' => $data['total_price'],
                ]);
            }

            // Auto-Verify if requested
            if ($request->has('auto_verify') && $request->auto_verify == '1') {
                $purchase->status = 'verified';
                $purchase->grn_number = 'GRN-' . $purchase->purchasing_number;
                $purchase->verified_at = now();
                $purchase->save();

                // GRN Logic: Add stock immediately
                foreach ($purchase->items as $item) {
                     $product = Product::find($item->product_id);
                     if($product) {
                        $product->quantity += $item->quantity;
                        $product->save();
                     }
                     
                     // Enable FIFO tracking
                     $item->remaining_quantity = $item->quantity;
                     $item->received_quantity = $item->quantity;
                     $item->save();
                }

                // Update Supplier Due Amount
                $supplier = Supplier::find($validated['supplier_id']);
                if($supplier) {
                    $supplier->due_amount += $purchase->total_amount;
                    $supplier->save();
                }
            }
            
            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase Order created ' . ($request->has('auto_verify') ? 'and verified' : '') . ' successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating purchase: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase (Detailed View / GRN Check).
     */
    public function show($id)
    {
        $purchase = Purchase::with(['items.product', 'supplier', 'user'])->findOrFail($id);
        return view('purchases.show', compact('purchase'));
    }
    
    /**
     * Verify Purchase (GRN Generation) and Stock Update.
     */
    public function verify(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);
        
        if ($purchase->status !== 'pending') {
            return back()->with('error', 'Purchase already verified or cancelled.');
        }

        DB::beginTransaction();
        try {
            $purchase->status = 'verified';
            $purchase->grn_number = 'GRN-' . $purchase->purchasing_number;
            $purchase->verified_at = now();
            $purchase->save();

            // GRN Logic: Add stock here
            foreach ($purchase->items as $item) {
                 $product = Product::find($item->product_id);
                 $product->quantity += $item->quantity; // Adding verifiable stock
                 $product->save();
                 
                 // Enable FIFO tracking
                 $item->remaining_quantity = $item->quantity;
                 $item->received_quantity = $item->quantity;
                 $item->save();
            }
            
            // Update Supplier Due Amount (Simple logic)
            $supplier = $purchase->supplier;
            $supplier->due_amount += $purchase->total_amount;
            $supplier->save();

            DB::commit();
            return back()->with('success', 'Purchase verified, GRN generated, and Stock updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    private function generatePurchasingNumber()
    {
        do {
            $number = 'PO-' . strtoupper(Str::random(8));
        } while (Purchase::where('purchasing_number', $number)->exists());
        return $number;
    }
}
