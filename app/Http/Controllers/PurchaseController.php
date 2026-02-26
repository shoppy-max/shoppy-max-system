<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    private const PAYMENT_METHODS = [
        'Cash',
        'Card',
        'Cheque',
        'Online Payment',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Purchase::with('supplier')->withCount('items')->latest('purchase_date');

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
        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Generate a suggested ID
        $suggestedNumber = 'PUR-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        return view('purchases.create', compact('suppliers', 'suggestedNumber', 'bankAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|string|max:100|unique:purchases,purchase_number',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'nullable|numeric|min:0.01',
            'payments.*.method' => 'nullable|in:Cash,Card,Cheque,Online Payment',
            'payments.*.date' => 'nullable|date',
            'payments.*.account_id' => 'nullable|exists:bank_accounts,id',
            'payments.*.account' => 'nullable|string|max:255',
            'payments.*.note' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $purchaseNumber = trim((string) $validated['purchase_number']);
            $discountType = $validated['discount_type'] ?? 'fixed';
            $discountValue = isset($validated['discount_value']) ? (float) $validated['discount_value'] : 0;
            $totals = $this->calculatePurchaseTotals($validated['items'], $discountType, $discountValue);
            $paymentsData = $this->normalizePayments($validated['payments'] ?? []);
            $paidAmount = (float) collect($paymentsData)->sum('amount');
            $this->ensurePaidAmountWithinTotal($paidAmount, $totals['net_total']);

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'currency' => 'LKR',
                'sub_total' => $totals['sub_total'],
                'discount_type' => $totals['discount_type'],
                'discount_value' => $totals['discount_value'],
                'discount_amount' => $totals['discount_amount'],
                'net_total' => $totals['net_total'],
                'paid_amount' => $paidAmount,
                'payments_data' => $paymentsData,
                'payment_method' => null,
                'payment_reference' => null,
                'payment_account' => null,
                'payment_note' => null,
            ]);

            foreach ($validated['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $price = round((float) $item['purchase_price'], 2);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => trim((string) $item['product_name']),
                    'quantity' => $quantity,
                    'purchase_price' => $price,
                    'total' => round($quantity * $price, 2),
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
        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'bankAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        // Validation similar to store, but unique purchase_number check ignores current id
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|string|max:100|unique:purchases,purchase_number,' . $purchase->id,
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'nullable|numeric|min:0.01',
            'payments.*.method' => 'nullable|in:Cash,Card,Cheque,Online Payment',
            'payments.*.date' => 'nullable|date',
            'payments.*.account_id' => 'nullable|exists:bank_accounts,id',
            'payments.*.account' => 'nullable|string|max:255',
            'payments.*.note' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $purchaseNumber = trim((string) $validated['purchase_number']);
            $discountType = $validated['discount_type'] ?? 'fixed';
            $discountValue = isset($validated['discount_value']) ? (float) $validated['discount_value'] : 0;
            $totals = $this->calculatePurchaseTotals($validated['items'], $discountType, $discountValue);
            $paymentsData = $this->normalizePayments($validated['payments'] ?? []);
            $paidAmount = (float) collect($paymentsData)->sum('amount');
            $this->ensurePaidAmountWithinTotal($paidAmount, $totals['net_total']);

            $purchase->update([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                // currency ignored/kept as LKR
                'sub_total' => $totals['sub_total'],
                'discount_type' => $totals['discount_type'],
                'discount_value' => $totals['discount_value'],
                'discount_amount' => $totals['discount_amount'],
                'net_total' => $totals['net_total'],
                'paid_amount' => $paidAmount,
                'payments_data' => $paymentsData,
                'payment_method' => null, // Deprecated
                'payment_reference' => null,
                'payment_account' => null, // Deprecated
                'payment_note' => null, // Deprecated
            ]);

            // Sync Items: Delete old and create new
            $purchase->items()->delete();

            foreach ($validated['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $price = round((float) $item['purchase_price'], 2);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => trim((string) $item['product_name']),
                    'quantity' => $quantity,
                    'purchase_price' => $price,
                    'total' => round($quantity * $price, 2),
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
     * Search suppliers for AJAX requests.
     */
    public function searchSuppliers(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $suppliers = Supplier::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('business_name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'business_name', 'mobile', 'phone']);

        return response()->json($suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->business_name ?? $supplier->name,
                'business_name' => $supplier->business_name,
                'contact_name' => $supplier->name,
                'mobile' => $supplier->mobile ?? $supplier->phone,
            ];
        }));
    }

    /**
     * Search products for purchase item lookup.
     */
    public function searchProducts(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->with(['variants.unit:id,name,short_name'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($query) {
                        $variantQuery->where('sku', 'like', "%{$query}%")
                            ->orWhere('unit_value', 'like', "%{$query}%")
                            ->orWhereHas('unit', function ($unitQuery) use ($query) {
                                $unitQuery->where('name', 'like', "%{$query}%")
                                    ->orWhere('short_name', 'like', "%{$query}%");
                            });
                    });
            })
            ->limit(25)
            ->get(['id', 'name']);

        return response()->json($products->map(function (Product $product) {
            $variantLabels = $product->variants
                ->map(function ($variant) {
                    $value = trim((string) ($variant->unit_value ?? ''));
                    $unit = trim((string) ($variant->unit->short_name ?? ''));
                    $label = trim(($value !== '' ? $value : '') . ($unit !== '' ? ' ' . $unit : ''));
                    return $label !== '' ? $label : null;
                })
                ->filter()
                ->unique()
                ->take(3)
                ->values();

            $displayName = $product->name;
            if ($variantLabels->isNotEmpty()) {
                $displayName .= ' (' . $variantLabels->implode(', ') . ')';
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'display_name' => $displayName,
            ];
        }));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
    }

    private function normalizePayments(array $paymentsInput): array
    {
        $accountIds = collect($paymentsInput)
            ->filter(fn ($payment) => is_array($payment))
            ->pluck('account_id')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $accountsMap = $accountIds->isNotEmpty()
            ? BankAccount::query()->whereIn('id', $accountIds)->get()->keyBy('id')
            : collect();

        return collect($paymentsInput)
            ->filter(fn ($payment) => is_array($payment))
            ->map(function (array $payment) use ($accountsMap) {
                $amount = isset($payment['amount']) ? round((float) $payment['amount'], 2) : 0;
                $method = trim((string) ($payment['method'] ?? ''));
                $accountId = isset($payment['account_id']) && $payment['account_id'] !== ''
                    ? (int) $payment['account_id']
                    : null;
                $selectedAccount = $accountId ? $accountsMap->get($accountId) : null;
                $legacyAccount = trim((string) ($payment['account'] ?? ''));
                $account = $selectedAccount?->display_label ?? $legacyAccount;
                $note = trim((string) ($payment['note'] ?? ''));
                $date = !empty($payment['date']) ? date('Y-m-d', strtotime((string) $payment['date'])) : now()->toDateString();
                $method = in_array($method, self::PAYMENT_METHODS, true) ? $method : 'Cash';

                return [
                    'amount' => $amount,
                    'method' => $method,
                    'date' => $date,
                    'account_id' => $selectedAccount?->id,
                    'account' => $account,
                    'note' => $note,
                ];
            })
            ->filter(fn (array $payment) => $payment['amount'] > 0)
            ->values()
            ->all();
    }

    private function calculatePurchaseTotals(array $items, string $discountType, float $discountValue): array
    {
        $subTotal = collect($items)->sum(function ($item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['purchase_price'] ?? 0);
            return $quantity * $price;
        });

        $subTotal = round(max($subTotal, 0), 2);
        $discountType = $discountType === 'percentage' ? 'percentage' : 'fixed';
        $discountValue = max(round($discountValue, 2), 0);

        $discountAmount = $discountType === 'percentage'
            ? ($subTotal * min($discountValue, 100) / 100)
            : $discountValue;

        $discountAmount = round(min($discountAmount, $subTotal), 2);
        $netTotal = round(max($subTotal - $discountAmount, 0), 2);

        return [
            'sub_total' => $subTotal,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'net_total' => $netTotal,
        ];
    }

    private function ensurePaidAmountWithinTotal(float $paidAmount, float $netTotal): void
    {
        if ($paidAmount <= $netTotal) {
            return;
        }

        throw ValidationException::withMessages([
            'payments' => 'Total paid amount cannot exceed the purchase net total.',
        ]);
    }
}
