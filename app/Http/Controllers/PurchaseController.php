<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Purchase::with('supplier')->latest('purchase_date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('business_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        // Stats for Dashboard Cards
        $totalPurchases = Purchase::count();
        $totalSpent = Purchase::sum('net_total');
        // Calculate total due (net_total - paid_amount)
        // Since we don't have a direct column, strict SQL or collection sum. 
        // SQL is better for performance.
        $totalDue = Purchase::query()->selectRaw('SUM(net_total - paid_amount) as due')->value('due') ?? 0;

        $purchases = $query->paginate(15);
        return view('purchases.index', compact('purchases', 'totalPurchases', 'totalSpent', 'totalDue'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        // Generate a suggested ID
        $suggestedNumber = 'PUR-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        return view('purchases.create', compact('suppliers', 'suggestedNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|unique:purchases,purchase_number',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total paid amount from payments array
        $paidAmount = 0;
        $paymentsData = [];
        
        if ($request->has('payments') && is_array($request->payments)) {
            foreach ($request->payments as $payment) {
                $paidAmount += floatval($payment['amount'] ?? 0);
            }
            $paymentsData = $request->payments;
        }

        $purchase = Purchase::create([
            'purchase_number' => $request->purchase_number,
            'supplier_id' => $request->supplier_id,
            'purchase_date' => $request->purchase_date,
            'currency' => $request->currency ?? 'LKR',
            'sub_total' => $request->sub_total,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value ?? 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'net_total' => $request->net_total,
            'paid_amount' => $paidAmount,
            'payments_data' => json_encode($paymentsData),
            'payment_method' => null, // Deprecated, keeping for backward compatibility
            'payment_reference' => $request->payment_reference,
            'payment_account' => null, // Deprecated
            'payment_note' => null, // Deprecated
        ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'total' => $item['quantity'] * $item['purchase_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating purchase: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['items', 'supplier']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Generate PDF/Print view for the purchase.
     */
    public function pdf(Purchase $purchase)
    {
        $purchase->load(['items', 'supplier']);
        return view('purchases.pdf', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::all();
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        // Validation similar to store, but unique purchase_number check ignores current id
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|unique:purchases,purchase_number,' . $purchase->id,
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total paid amount from payments array
            $paidAmount = 0;
            $paymentsData = [];
            
            if ($request->has('payments') && is_array($request->payments)) {
                foreach ($request->payments as $payment) {
                    $paidAmount += floatval($payment['amount'] ?? 0);
                }
                $paymentsData = $request->payments;
            }

            $purchase->update([
                'purchase_number' => $request->purchase_number,
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                // currency ignored/kept as LKR
                'sub_total' => $request->sub_total,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'net_total' => $request->net_total,
                'paid_amount' => $paidAmount,
                'payments_data' => json_encode($paymentsData),
                'payment_method' => null, // Deprecated
                'payment_reference' => $request->payment_reference,
                'payment_account' => null, // Deprecated
                'payment_note' => null, // Deprecated
            ]);

            // Sync Items: Delete old and create new
            $purchase->items()->delete();

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'total' => $item['quantity'] * $item['purchase_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating purchase: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
    }
}
