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
            'items.*.product_id' => 'required|exists:products,id',
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
            
            // Calculate Total
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['purchasing_price'];
            }
            $purchase->total_amount = $totalAmount;
            $purchase->save();

            // Save Items
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'purchasing_price' => $item['purchasing_price'],
                    'total_price' => $item['quantity'] * $item['purchasing_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase Order created successfully.');

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
