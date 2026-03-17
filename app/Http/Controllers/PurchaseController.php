<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\ProductVariant;
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
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
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

        $this->ensureUniquePurchaseItems($validated['items']);

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
                $this->createPurchaseItemAndApplyStock($purchase, $item);
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully.');

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
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

        $this->ensureUniquePurchaseItems($validated['items']);

        $requestedPurchaseNumber = trim((string) $validated['purchase_number']);
        $currentPurchaseNumber = trim((string) $purchase->purchase_number);
        $requestedPurchaseDate = (string) $validated['purchase_date'];
        $currentPurchaseDate = optional($purchase->purchase_date)->format('Y-m-d');

        if ($requestedPurchaseNumber !== $currentPurchaseNumber || $requestedPurchaseDate !== $currentPurchaseDate) {
            throw ValidationException::withMessages([
                'purchase_number' => 'Purchasing ID cannot be changed after creation.',
                'purchase_date' => 'Purchase date cannot be changed after creation.',
            ]);
        }

        DB::beginTransaction();
        try {
            $discountType = $validated['discount_type'] ?? 'fixed';
            $discountValue = isset($validated['discount_value']) ? (float) $validated['discount_value'] : 0;
            $totals = $this->calculatePurchaseTotals($validated['items'], $discountType, $discountValue);
            $paymentsData = $this->normalizePayments($validated['payments'] ?? []);
            $paidAmount = (float) collect($paymentsData)->sum('amount');
            $this->ensurePaidAmountWithinTotal($paidAmount, $totals['net_total']);

            $purchase->load('items');
            $this->revertPurchaseStock($purchase->items, 'update');

            $purchase->update([
                'purchase_number' => $currentPurchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $currentPurchaseDate,
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
                $this->createPurchaseItemAndApplyStock($purchase, $item);
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $suppliers = Supplier::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('business_name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('landline', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'business_name', 'mobile', 'landline', 'email']);

        return response()->json($suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->business_name ?: $supplier->name,
                'business_name' => $supplier->business_name,
                'contact_name' => $supplier->name,
                'mobile' => $supplier->mobile ?: $supplier->landline,
            ];
        }));
    }

    /**
     * Search products for purchase item lookup.
     */
    public function searchProducts(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $variants = ProductVariant::query()
            ->with([
                'product:id,name,description',
                'unit:id,name,short_name',
            ])
            ->whereHas('product')
            ->where(function ($q) use ($query) {
                $q->where('sku', 'like', "%{$query}%")
                    ->orWhere('unit_value', 'like', "%{$query}%")
                    ->orWhereHas('unit', function ($unitQuery) use ($query) {
                        $unitQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('short_name', 'like', "%{$query}%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($query) {
                        $productQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
            })
            ->limit(50)
            ->get(['id', 'product_id', 'unit_id', 'unit_value', 'sku']);

        return response()->json($variants
            ->map(function (ProductVariant $variant) use ($query) {
                if (!$variant->product) {
                    return null;
                }

                $productName = trim((string) $variant->product->name);
                $variantLabel = $this->buildVariantLabel($variant);
                $variantDetail = $this->buildVariantDetailLabel($variant);
                $selectedLabel = $productName;

                if ($variantLabel !== '') {
                    $selectedLabel .= ' (' . $variantLabel . ')';
                }

                if (!empty($variant->sku)) {
                    $selectedLabel .= ' [' . $variant->sku . ']';
                }

                return [
                    'id' => $variant->id,
                    'variant_id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'name' => $productName,
                    'product_name' => $productName,
                    'variant_label' => $variantLabel,
                    'variant_detail' => $variantDetail,
                    'dropdown_label' => $variantLabel !== '' ? $productName . ' (' . $variantLabel . ')' : $productName,
                    'selected_label' => $selectedLabel,
                    'display_name' => $selectedLabel,
                    'sku' => $variant->sku,
                    'search_rank' => $this->rankPurchaseSearchResult($query, $productName, (string) $variant->sku, $variantLabel),
                ];
            })
            ->filter()
            ->sortBy([
                ['search_rank', 'asc'],
                ['product_name', 'asc'],
                ['variant_label', 'asc'],
                ['sku', 'asc'],
            ])
            ->values()
            ->take(30)
            ->map(function (array $result) {
                unset($result['search_rank']);
                return $result;
            })
            ->values());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            $purchase->load('items');
            $this->revertPurchaseStock($purchase->items, 'delete');
            $purchase->delete();

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->route('purchases.index')->with(
                'error',
                collect($e->errors())->flatten()->first() ?: 'Cannot delete purchase because stock has already been used by orders.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('purchases.index')->with('error', 'Error deleting purchase: ' . $e->getMessage());
        }
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

    private function createPurchaseItemAndApplyStock(Purchase $purchase, array $itemData): void
    {
        $variant = $this->resolveVariantForPurchaseItem($itemData);
        $quantity = (int) ($itemData['quantity'] ?? 0);
        $price = round((float) ($itemData['purchase_price'] ?? 0), 2);
        $productName = trim((string) ($itemData['product_name'] ?? ''));
        if ($productName === '') {
            $productName = $this->buildVariantDisplayName($variant);
        }

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $variant->product_id,
            'stock_variant_id' => $variant->id,
            'product_name' => $productName,
            'quantity' => $quantity,
            'purchase_price' => $price,
            'total' => round($quantity * $price, 2),
        ]);

        $variant->increment('quantity', $quantity);
    }

    private function revertPurchaseStock($items, string $action): void
    {
        foreach ($items as $item) {
            $variantId = (int) ($item->stock_variant_id ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $variant = ProductVariant::query()->whereKey($variantId)->lockForUpdate()->first();
            if (!$variant) {
                continue;
            }

            $quantity = (int) ($item->quantity ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            if ((int) $variant->quantity < $quantity) {
                $variantDisplayName = $this->buildVariantDisplayName($variant);
                throw ValidationException::withMessages([
                    'purchase' => "Cannot {$action} this purchase because stock for {$variantDisplayName} would go below zero.",
                ]);
            }

            $variant->decrement('quantity', $quantity);
        }
    }

    private function resolveVariantForPurchaseItem(array $itemData): ProductVariant
    {
        $variantId = isset($itemData['product_variant_id']) && $itemData['product_variant_id'] !== ''
            ? (int) $itemData['product_variant_id']
            : null;

        if ($variantId) {
            $variant = ProductVariant::with(['product', 'unit'])->find($variantId);
            if (!$variant || !$variant->product) {
                throw ValidationException::withMessages([
                    'items' => 'A selected purchase item variant is invalid.',
                ]);
            }

            return $variant;
        }

        $productId = isset($itemData['product_id']) && $itemData['product_id'] !== ''
            ? (int) $itemData['product_id']
            : null;

        if (!$productId) {
            throw ValidationException::withMessages([
                'items' => 'Select a valid product variant for each purchase item.',
            ]);
        }

        $variants = ProductVariant::with(['product', 'unit'])
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Selected product does not have an active variant.',
            ]);
        }

        if ($variants->count() > 1) {
            throw ValidationException::withMessages([
                'items' => 'Selected product has multiple variants. Please reselect and choose a specific variant.',
            ]);
        }

        return $variants->first();
    }

    private function buildVariantDisplayName(ProductVariant $variant): string
    {
        $productName = trim((string) ($variant->product->name ?? 'Product'));
        $unitLabel = $this->buildVariantLabel($variant);

        $name = $productName;
        if ($unitLabel !== '') {
            $name .= ' (' . $unitLabel . ')';
        }
        if (!empty($variant->sku)) {
            $name .= ' [' . $variant->sku . ']';
        }

        return $name;
    }

    private function buildVariantLabel(ProductVariant $variant): string
    {
        $unitValue = trim((string) ($variant->unit_value ?? ''));
        $unitShort = trim((string) ($variant->unit->short_name ?? ''));
        $unitName = trim((string) ($variant->unit->name ?? ''));
        $unitBaseLabel = $unitShort !== '' ? $unitShort : $unitName;

        if ($unitValue !== '') {
            return trim($unitValue . ($unitBaseLabel !== '' ? ' ' . $unitBaseLabel : ''));
        }

        return $unitBaseLabel;
    }

    private function buildVariantDetailLabel(ProductVariant $variant): string
    {
        $compact = $this->buildVariantLabel($variant);
        $unitName = trim((string) ($variant->unit->name ?? ''));

        if ($compact === '') {
            return $unitName;
        }

        if ($unitName === '') {
            return $compact;
        }

        $compactLower = mb_strtolower($compact);
        $unitNameLower = mb_strtolower($unitName);

        if ($compactLower === $unitNameLower || str_ends_with($compactLower, $unitNameLower)) {
            return $compact;
        }

        return $compact . ' • ' . $unitName;
    }

    private function rankPurchaseSearchResult(string $query, string $productName, string $sku, string $variantLabel): int
    {
        $needle = mb_strtolower(trim($query));
        $product = mb_strtolower($productName);
        $sku = mb_strtolower($sku);
        $variant = mb_strtolower($variantLabel);

        return match (true) {
            $sku !== '' && $sku === $needle => 0,
            $product === $needle => 1,
            $product !== '' && str_starts_with($product, $needle) => 2,
            $sku !== '' && str_starts_with($sku, $needle) => 3,
            $variant !== '' && str_starts_with($variant, $needle) => 4,
            $product !== '' && str_contains($product, $needle) => 5,
            $sku !== '' && str_contains($sku, $needle) => 6,
            $variant !== '' && str_contains($variant, $needle) => 7,
            default => 8,
        };
    }

    private function ensureUniquePurchaseItems(array $items): void
    {
        $seen = [];

        foreach ($items as $index => $item) {
            $variantId = isset($item['product_variant_id']) && $item['product_variant_id'] !== ''
                ? (int) $item['product_variant_id']
                : null;
            $productId = isset($item['product_id']) && $item['product_id'] !== ''
                ? (int) $item['product_id']
                : null;

            $key = $variantId
                ? 'variant:' . $variantId
                : ($productId ? 'product:' . $productId : null);

            if (!$key) {
                continue;
            }

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'items' => 'The same product variant cannot be added more than once in a single purchase.',
                ]);
            }

            $seen[$key] = $index;
        }
    }
}
