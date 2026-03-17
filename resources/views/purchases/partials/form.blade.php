@php
    $isEditing = isset($purchase) && $purchase;

    $selectedSupplierId = old('supplier_id', $isEditing ? $purchase->supplier_id : null);
    $selectedSupplier = null;
    if ($selectedSupplierId) {
        $selectedSupplier = $suppliers->firstWhere('id', (int) $selectedSupplierId);
    } elseif ($isEditing && $purchase->supplier) {
        $selectedSupplier = $purchase->supplier;
    }

    $initialSupplier = $selectedSupplier ? [
        'id' => $selectedSupplier->id,
        'name' => $selectedSupplier->business_name ?: $selectedSupplier->name,
        'business_name' => $selectedSupplier->business_name,
        'contact_name' => $selectedSupplier->name,
        'mobile' => $selectedSupplier->mobile ?: $selectedSupplier->landline,
    ] : null;

    $oldItems = old('items');
    if (is_array($oldItems) && count($oldItems) > 0) {
        $initialItems = collect($oldItems)->map(function ($item) {
            return [
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? '',
                'quantity' => (int) ($item['quantity'] ?? 1),
                'purchase_price' => (float) ($item['purchase_price'] ?? 0),
            ];
        })->values()->all();
    } elseif ($isEditing) {
        $initialItems = $purchase->items->map(function ($item) {
            return [
                'product_variant_id' => $item->stock_variant_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'purchase_price' => (float) $item->purchase_price,
            ];
        })->values()->all();
    } else {
        $initialItems = [
            [
                'product_variant_id' => null,
                'product_id' => null,
                'product_name' => '',
                'quantity' => 1,
                'purchase_price' => 0,
            ],
        ];
    }

    $oldPayments = old('payments');
    if (is_array($oldPayments)) {
        $initialPayments = collect($oldPayments)->map(function ($payment) {
            return [
                'amount' => (float) ($payment['amount'] ?? 0),
                'method' => $payment['method'] ?? 'Cash',
                'date' => $payment['date'] ?? now()->toDateString(),
                'account_id' => $payment['account_id'] ?? null,
                'account' => $payment['account'] ?? '',
                'note' => $payment['note'] ?? '',
            ];
        })->values()->all();
    } elseif ($isEditing && is_array($purchase->payments_data)) {
        $initialPayments = collect($purchase->payments_data)->map(function ($payment) {
            return [
                'amount' => (float) ($payment['amount'] ?? 0),
                'method' => $payment['method'] ?? 'Cash',
                'date' => $payment['date'] ?? now()->toDateString(),
                'account_id' => $payment['account_id'] ?? null,
                'account' => $payment['account'] ?? '',
                'note' => $payment['note'] ?? '',
            ];
        })->values()->all();
    } else {
        $initialPayments = [];
    }

    $initialDate = old('purchase_date', $isEditing ? optional($purchase->purchase_date)->format('Y-m-d') : now()->toDateString());
    $initialNumber = old('purchase_number', $isEditing ? $purchase->purchase_number : ($suggestedNumber ?? ''));
    $initialStatus = old('status', $isEditing ? ($purchase->status ?? 'pending') : 'pending');
    $statusOptions = \App\Models\Purchase::STATUSES;
    if ($isEditing) {
        $currentStatusIndex = array_search($purchase->status ?? 'pending', \App\Models\Purchase::STATUSES, true);
        if ($currentStatusIndex === false) {
            $currentStatusIndex = 0;
        }
        $statusOptions = array_slice(\App\Models\Purchase::STATUSES, $currentStatusIndex, 2);
        if (empty($statusOptions)) {
            $statusOptions = [$purchase->status ?? 'pending'];
        }
    }
    $initialDiscountType = old('discount_type', $isEditing ? ($purchase->discount_type ?: 'fixed') : 'fixed');
    $initialDiscountValue = old('discount_value', $isEditing ? (float) $purchase->discount_value : 0);
    $bankAccountOptions = collect($bankAccounts ?? [])
        ->map(fn ($account) => [
            'id' => (string) $account->id,
            'label' => $account->display_label,
        ])
        ->values()
        ->all();
@endphp

<form method="POST" action="{{ $formAction }}" x-data="purchaseForm(@js([
    'initialSupplier' => $initialSupplier,
    'initialItems' => $initialItems,
    'initialPayments' => $initialPayments,
    'bankAccounts' => $bankAccountOptions,
    'discountType' => $initialDiscountType,
    'discountValue' => $initialDiscountValue,
    'supplierSearchUrl' => route('purchases.search-suppliers'),
    'productSearchUrl' => route('purchases.search-products'),
]))" x-init="init()" @submit.prevent="submitForm">
    @csrf
    @if(($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
            <p class="font-semibold">Please fix the following issues:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-6">
        <x-form-section title="Purchase Details" description="Supplier, date, and purchasing ID information.">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="md:col-span-2" @click.outside="showSupplierDropdown = false">
                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Supplier <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg x-show="!supplierLoading" class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <svg x-show="supplierLoading" class="h-4 w-4 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                        <input type="text"
                               x-model="supplierSearch"
                               @input.debounce.300ms="onSupplierInput()"
                               @focus="onSupplierFocus()"
                               @keydown.escape="showSupplierDropdown = false"
                               @keydown.arrow-down.prevent="navigateSupplier(1)"
                               @keydown.arrow-up.prevent="navigateSupplier(-1)"
                               @keydown.enter.prevent="selectHighlightedSupplier()"
                               :class="selectedSupplier ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700' : 'border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700'"
                               class="block w-full rounded-lg border p-2.5 pl-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:text-white"
                               placeholder="Search supplier by business name, contact, or mobile"
                               autocomplete="off"
                               required>
                        <input type="hidden" name="supplier_id" :value="selectedSupplier ? selectedSupplier.id : ''">

                        <button type="button"
                                x-show="selectedSupplier"
                                @click="clearSupplier()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition-colors hover:text-red-600"
                                title="Clear supplier">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>

                        {{-- Dropdown results --}}
                        <div x-show="showSupplierDropdown"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                             style="display: none;">

                            {{-- Results list --}}
                            <template x-for="(supplier, sIdx) in supplierResults" :key="supplier.id">
                                <button type="button"
                                        @click="selectSupplier(supplier)"
                                        @mouseenter="highlightedSupplierIndex = sIdx"
                                        :class="highlightedSupplierIndex === sIdx ? 'bg-blue-50 dark:bg-gray-600' : ''"
                                        class="group block w-full border-b border-gray-100 px-4 py-3 text-left transition-colors hover:bg-blue-50 dark:border-gray-600 dark:hover:bg-gray-600">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-300" x-text="supplier.name"></p>
                                        <svg class="h-4 w-4 text-gray-300 opacity-0 transition-opacity group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-300">
                                        <span x-show="supplier.contact_name && supplier.business_name" class="inline-flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span x-text="supplier.contact_name"></span>
                                            <span class="text-gray-300 dark:text-gray-500">·</span>
                                        </span>
                                        <span x-show="supplier.mobile" class="inline-flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            <span x-text="supplier.mobile"></span>
                                        </span>
                                    </p>
                                </button>
                            </template>

                            {{-- No results --}}
                            <div x-show="supplierResults.length === 0 && !supplierLoading" class="px-4 py-6 text-center">
                                <svg class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No suppliers found</p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Try a different search term</p>
                            </div>

                            {{-- Loading --}}
                            <div x-show="supplierLoading && supplierResults.length === 0" class="px-4 py-4 text-center" style="display: none;">
                                <svg class="mx-auto h-5 w-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Searching suppliers...</p>
                            </div>
                        </div>
                    </div>
                    <p x-show="selectedSupplier" x-transition class="mt-1.5 flex items-center gap-1 text-xs text-blue-600 dark:text-blue-300">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Selected: <span x-text="selectedSupplier ? selectedSupplier.name : ''" class="font-medium"></span>
                    </p>
                    @error('supplier_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Purchase Date <span class="text-red-500">*</span></label>
                    @if($isEditing)
                        <input type="hidden" name="purchase_date" value="{{ $initialDate }}">
                        <input type="date" value="{{ $initialDate }}" class="block w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400" disabled>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Purchase date is locked after creation.</p>
                    @else
                        <input type="date" name="purchase_date" value="{{ $initialDate }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                    @endif
                    @error('purchase_date')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Purchasing ID <span class="text-red-500">*</span></label>
                    @if($isEditing)
                        <input type="text" name="purchase_number" value="{{ $initialNumber }}" class="block w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400" required readonly>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Purchasing ID is locked after creation.</p>
                    @else
                        <input type="text" name="purchase_number" value="{{ $initialNumber }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required readonly>
                    @endif
                    @error('purchase_number')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Status <span class="text-red-500">*</span></label>
                    @if($isEditing)
                        <select name="status" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            @foreach($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}" @selected($initialStatus === $statusOption)>{{ ucfirst($statusOption) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Status can stay the same or move forward one step only.</p>
                    @else
                        <input type="hidden" name="status" value="pending">
                        <div class="inline-flex rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            Pending
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">New purchases always start at pending.</p>
                    @endif
                    @error('status')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Purchase Items" description="Add purchased products with quantity and purchase price.">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">At least one item is required.</p>
                <button type="button" @click="addItem()" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Item
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(item, index) in items" :key="item.rowId">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" @click.outside="item.showResults = false">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Item <span x-text="index + 1"></span></h4>
                            <button type="button" @click="removeItem(index)" class="text-sm text-red-600 hover:text-red-700" :disabled="items.length === 1">Remove</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-6">
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Product <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text"
                                           x-model="item.product_name"
                                           @input="item.product_variant_id = null; item.product_id = null"
                                           @input.debounce.250ms="searchProducts(index)"
                                           @focus="if (!item.product_variant_id && !item.product_id && (item.product_name || '').trim().length >= 2) { searchProducts(index); }"
                                           @keydown.escape="item.showResults = false"
                                           @keydown.arrow-down.prevent="if (!item.product_variant_id && !item.product_id) navigateProduct(index, 1)"
                                           @keydown.arrow-up.prevent="if (!item.product_variant_id && !item.product_id) navigateProduct(index, -1)"
                                           @keydown.enter.prevent="if (!item.product_variant_id && !item.product_id) selectHighlightedProduct(index)"
                                           :readonly="!!item.product_variant_id || !!item.product_id"
                                           :class="item.product_variant_id ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700' : 'border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700'"
                                           class="block w-full rounded-lg border p-2.5 pr-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:text-white"
                                           placeholder="Search product name, SKU, or unit"
                                           autocomplete="off"
                                           required>

                                    <button type="button"
                                            x-show="item.product_variant_id || item.product_id"
                                            @click="clearProduct(index)"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition-colors hover:text-red-600"
                                            title="Clear selected product"
                                            style="display: none;">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>

                                    <div x-show="item.showResults"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="absolute z-20 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                                         :class="shouldOpenUpward($el) ? 'bottom-full mb-1' : 'top-full mt-1'"
                                        style="display: none;">
                                        <template x-for="(product, pIdx) in item.results" :key="product.id">
                                            <button type="button"
                                                    @click="selectProduct(index, product)"
                                                    @mouseenter="item.highlightedIndex = pIdx"
                                                    :class="item.highlightedIndex === pIdx ? 'bg-blue-50 dark:bg-gray-600' : ''"
                                                    class="block w-full border-b border-gray-100 px-4 py-3 text-left transition-colors hover:bg-blue-50 dark:border-gray-600 dark:hover:bg-gray-600">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="product.product_name || product.name"></p>
                                                    <span x-show="product.variant_detail || product.variant_label" class="inline-flex rounded-full border border-blue-200 bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-900/40 dark:text-blue-300" x-text="product.variant_detail || product.variant_label"></span>
                                                </div>
                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                                                    <span class="inline-flex rounded border border-gray-200 bg-gray-100 px-2 py-0.5 font-mono text-[10px] text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" x-text="product.sku ? ('SKU: ' + product.sku) : ('Product ID: ' + product.product_id)"></span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="item.loading" class="px-4 py-4 text-center text-xs text-gray-500 dark:text-gray-400" style="display: none;">
                                            Searching products...
                                        </div>
                                        <div x-show="!item.loading && item.results.length === 0" class="px-4 py-4 text-center text-xs text-gray-500 dark:text-gray-400" style="display: none;">
                                            No matching products found
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" :name="`items[${index}][product_variant_id]`" :value="item.product_variant_id || ''">
                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id || ''">
                                <input type="hidden" :name="`items[${index}][product_name]`" :value="item.product_name">
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty <span class="text-red-500">*</span></label>
                                <input type="number" min="1" step="1" inputmode="numeric" x-model="item.quantity" @keydown="blockDecimalInput($event)" @blur="normalizeWholeQuantity(item, true)" :name="`items[${index}][quantity]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit Price <span class="text-red-500">*</span></label>
                                <input type="number" min="0" step="0.01" x-model="item.purchase_price" :name="`items[${index}][purchase_price]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-right text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Line Total</label>
                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-2.5 text-right text-sm font-semibold text-blue-700 dark:border-blue-900 dark:bg-blue-900/30 dark:text-blue-200">
                                    Rs. <span x-text="lineTotal(item).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-form-section>

        <x-form-section title="Payment Summary" description="Review totals, apply discount, and track remaining due.">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Discount</label>
                    <div class="flex gap-2">
                        <input type="number" min="0" step="0.01" x-model="discount_value" name="discount_value" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-right text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="0.00">
                        <select x-model="discount_type" name="discount_type" class="w-40 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="fixed">Fixed (Rs.)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Percentage discounts are capped at 100%.</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <div class="flex items-center justify-between border-b border-gray-200 py-2 text-sm dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                        <span class="font-semibold text-gray-900 dark:text-white">Rs. <span x-text="subTotal.toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-200 py-2 text-sm dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-300">Discount</span>
                        <span class="font-semibold text-green-700 dark:text-green-300">- Rs. <span x-text="discountAmount.toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-200 py-2 text-sm dark:border-gray-700">
                        <span class="font-medium text-gray-700 dark:text-gray-200">Net Total</span>
                        <span class="text-base font-bold text-blue-700 dark:text-blue-300">Rs. <span x-text="netTotal.toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-200 py-2 text-sm dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-300">Paid Amount</span>
                        <span class="font-semibold text-gray-900 dark:text-white">Rs. <span x-text="totalPaid.toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-200">Balance Due</span>
                        <span class="font-bold" :class="balanceDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">Rs. <span x-text="balanceDue.toFixed(2)"></span></span>
                    </div>
                </div>

                <input type="hidden" name="sub_total" :value="subTotal.toFixed(2)">
                <input type="hidden" name="discount_amount" :value="discountAmount.toFixed(2)">
                <input type="hidden" name="net_total" :value="netTotal.toFixed(2)">
            </div>
        </x-form-section>

        <x-form-section title="Payments" description="Optional. Add one or more payment entries.">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">If no payment is added, paid amount remains 0.</p>
                <button type="button" @click="addPayment()" class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Payment
                </button>
            </div>

            @if(collect($bankAccounts ?? [])->isEmpty())
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300">
                    No active bank accounts found. Add one in
                    <a href="{{ route('bank-accounts.create') }}" class="font-medium underline">Bank Accounts</a>.
                </div>
            @endif

            <div class="space-y-4" x-show="payments.length > 0">
                <template x-for="(payment, index) in payments" :key="payment.rowId">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Payment <span x-text="index + 1"></span></h4>
                            <button type="button" @click="removePayment(index)" class="text-sm text-red-600 hover:text-red-700">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                            <div>
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</label>
                                <input type="number" min="0.01" step="0.01" x-model="payment.amount" :name="`payments[${index}][amount]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-right text-sm text-gray-900 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Method</label>
                                <select x-model="payment.method" :name="`payments[${index}][method]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Online Payment">Online Payment</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</label>
                                <input type="date" x-model="payment.date" :name="`payments[${index}][date]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Account</label>
                                <select x-model="payment.account_id" :name="`payments[${index}][account_id]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select account</option>
                                    <template x-for="account in bankAccounts" :key="account.id">
                                        <option :value="account.id" x-text="account.label"></option>
                                    </template>
                                </select>
                                <input type="hidden" :name="`payments[${index}][account]`" :value="payment.account_id ? '' : payment.account">
                                <p x-show="!payment.account_id && payment.account" class="mt-1 text-xs text-amber-600 dark:text-amber-300">
                                    Legacy account: <span x-text="payment.account"></span>
                                </p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Note</label>
                                <input type="text" x-model="payment.note" :name="`payments[${index}][note]`" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Ref / note">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="payments.length === 0" class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                No payment entries added.
            </div>

            @error('payments')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </x-form-section>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                {{ $submitLabel }}
            </button>
            <a href="{{ $cancelRoute }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                Cancel
            </a>
        </div>
    </div>
</form>

<script>
    function purchaseForm(config) {
        return {
            supplierSearch: config.initialSupplier ? config.initialSupplier.name : '',
            selectedSupplier: config.initialSupplier || null,
            supplierResults: [],
            showSupplierDropdown: false,
            supplierLoading: false,
            highlightedSupplierIndex: -1,
            _supplierLocked: !!config.initialSupplier,
            _supplierAbort: null,
            items: [],
            payments: [],
            bankAccounts: Array.isArray(config.bankAccounts) ? config.bankAccounts : [],
            discount_type: config.discountType || 'fixed',
            discount_value: Number(config.discountValue || 0),

            init() {
                this.items = (config.initialItems || []).map((item, index) => this.normalizeItem(item, index));
                if (this.items.length === 0) {
                    this.items.push(this.createItem());
                }

                this.payments = (config.initialPayments || []).map((payment, index) => this.normalizePayment(payment, index));
            },

            notify(type, message) {
                if (typeof window.Swal !== 'undefined') {
                    window.Swal.fire({
                        icon: type,
                        title: message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2800,
                        timerProgressBar: true,
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#1f2937',
                    });
                    return;
                }

                alert(message);
            },

            normalizeItem(item, index) {
                return {
                    rowId: `item-${Date.now()}-${index}-${Math.random().toString(16).slice(2)}`,
                    product_variant_id: item.product_variant_id || null,
                    product_id: item.product_id || null,
                    product_name: item.product_name || '',
                    quantity: Number(item.quantity || 1),
                    purchase_price: Number(item.purchase_price || 0),
                    results: [],
                    showResults: false,
                    loading: false,
                    highlightedIndex: -1,
                };
            },

            createItem() {
                return this.normalizeItem({}, this.items.length + 1);
            },

            hasDuplicateProduct(product, currentIndex) {
                const targetVariantId = String(product.variant_id || product.id || '');
                const targetProductId = String(product.product_id || '');

                return this.items.some((item, index) => {
                    if (index === currentIndex) {
                        return false;
                    }

                    if (targetVariantId && String(item.product_variant_id || '') === targetVariantId) {
                        return true;
                    }

                    return !targetVariantId
                        && targetProductId
                        && String(item.product_id || '') === targetProductId;
                });
            },

            normalizePayment(payment, index) {
                const today = new Date().toISOString().split('T')[0];

                return {
                    rowId: `payment-${Date.now()}-${index}-${Math.random().toString(16).slice(2)}`,
                    amount: Number(payment.amount || 0),
                    method: payment.method || 'Cash',
                    date: payment.date || today,
                    account_id: payment.account_id ? String(payment.account_id) : '',
                    account: payment.account || '',
                    note: payment.note || '',
                };
            },

            onSupplierInput() {
                // User typed something — if we had a selection, deselect it
                if (this._supplierLocked) {
                    this._supplierLocked = false;
                    this.selectedSupplier = null;
                }
                this.fetchSuppliers();
            },

            onSupplierFocus() {
                if (this._supplierLocked || this.selectedSupplier) return;
                const q = (this.supplierSearch || '').trim();
                if (q.length >= 2) {
                    this.fetchSuppliers();
                }
            },

            async fetchSuppliers() {
                const query = (this.supplierSearch || '').trim();

                if (query.length < 2) {
                    this.supplierResults = [];
                    this.showSupplierDropdown = false;
                    this.supplierLoading = false;
                    return;
                }

                // Cancel any in-flight request
                if (this._supplierAbort) {
                    this._supplierAbort.abort();
                }
                this._supplierAbort = new AbortController();

                this.supplierLoading = true;
                this.showSupplierDropdown = true;

                try {
                    const response = await fetch(
                        `${config.supplierSearchUrl}?q=${encodeURIComponent(query)}`,
                        { signal: this._supplierAbort.signal }
                    );
                    const data = await response.json();
                    this.supplierResults = Array.isArray(data) ? data : [];
                    this.supplierLoading = false;
                    this.highlightedSupplierIndex = -1;
                    // Keep dropdown open even with 0 results to show "No suppliers found"
                    this.showSupplierDropdown = true;
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        this.supplierResults = [];
                        this.supplierLoading = false;
                        this.showSupplierDropdown = false;
                    }
                }
            },

            selectSupplier(supplier) {
                this.selectedSupplier = supplier;
                this.supplierSearch = supplier.name || '';
                this._supplierLocked = true;
                this.supplierResults = [];
                this.showSupplierDropdown = false;
                this.supplierLoading = false;
            },

            clearSupplier() {
                this.selectedSupplier = null;
                this.supplierSearch = '';
                this._supplierLocked = false;
                this.supplierResults = [];
                this.showSupplierDropdown = false;
                this.supplierLoading = false;
                this.highlightedSupplierIndex = -1;
            },

            navigateSupplier(direction) {
                if (!this.showSupplierDropdown || this.supplierResults.length === 0) return;
                this.highlightedSupplierIndex = Math.max(-1, Math.min(
                    this.highlightedSupplierIndex + direction,
                    this.supplierResults.length - 1
                ));
            },

            selectHighlightedSupplier() {
                if (this.highlightedSupplierIndex >= 0 && this.highlightedSupplierIndex < this.supplierResults.length) {
                    this.selectSupplier(this.supplierResults[this.highlightedSupplierIndex]);
                }
            },

            async searchProducts(index) {
                const item = this.items[index];
                if (!item) {
                    return;
                }

                const query = (item.product_name || '').trim();
                if (query.length < 2) {
                    item.results = [];
                    item.showResults = false;
                    item.loading = false;
                    return;
                }

                item.loading = true;
                item.showResults = true;

                try {
                    const response = await fetch(`${config.productSearchUrl}?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    item.results = Array.isArray(data) ? data : [];
                    item.showResults = true;
                    item.loading = false;
                    item.highlightedIndex = -1;
                } catch (error) {
                    item.results = [];
                    item.showResults = true;
                    item.loading = false;
                }
            },

            selectProduct(index, product) {
                const item = this.items[index];
                if (!item) {
                    return;
                }

                if (this.hasDuplicateProduct(product, index)) {
                    this.notify('warning', 'This product variant is already added to the purchase.');
                    item.results = [];
                    item.showResults = false;
                    item.loading = false;
                    item.highlightedIndex = -1;
                    return;
                }

                item.product_variant_id = product.variant_id || product.id || null;
                item.product_id = product.product_id || null;
                item.product_name = product.selected_label || product.display_name || product.product_name || product.name || '';
                item.results = [];
                item.showResults = false;
                item.loading = false;
                item.highlightedIndex = -1;
            },

            clearProduct(index) {
                const item = this.items[index];
                if (!item) {
                    return;
                }

                item.product_variant_id = null;
                item.product_id = null;
                item.product_name = '';
                item.results = [];
                item.showResults = false;
                item.loading = false;
                item.highlightedIndex = -1;
            },

            navigateProduct(index, direction) {
                const item = this.items[index];
                if (!item || !item.showResults || item.results.length === 0) return;
                item.highlightedIndex = Math.max(-1, Math.min(
                    item.highlightedIndex + direction,
                    item.results.length - 1
                ));
            },

            selectHighlightedProduct(index) {
                const item = this.items[index];
                if (!item) return;
                if (item.highlightedIndex >= 0 && item.highlightedIndex < item.results.length) {
                    this.selectProduct(index, item.results[item.highlightedIndex]);
                }
            },

            shouldOpenUpward(el) {
                if (!el) return false;
                const rect = el.closest('.relative')?.getBoundingClientRect();
                if (!rect) return false;
                const spaceBelow = window.innerHeight - rect.bottom;
                return spaceBelow < 250;
            },

            addItem() {
                this.items.push(this.createItem());
            },

            removeItem(index) {
                if (this.items.length <= 1) {
                    return;
                }
                this.items.splice(index, 1);
            },

            addPayment() {
                this.payments.push(this.normalizePayment({}, this.payments.length + 1));
            },

            removePayment(index) {
                this.payments.splice(index, 1);
            },

            lineTotal(item) {
                const qty = Number(item.quantity || 0);
                const price = Number(item.purchase_price || 0);
                if (!Number.isFinite(qty) || !Number.isFinite(price)) {
                    return 0;
                }

                return Math.max(qty, 0) * Math.max(price, 0);
            },

            blockDecimalInput(event) {
                if (['.', ',', 'e', 'E', '+', '-'].includes(event.key)) {
                    event.preventDefault();
                }
            },

            normalizeWholeQuantity(item, notifyIfAdjusted = false) {
                const raw = Number(item.quantity);

                if (!Number.isFinite(raw) || raw <= 0) {
                    item.quantity = '';
                    return;
                }

                const whole = Math.floor(raw);
                if (whole !== raw && notifyIfAdjusted) {
                    this.notify('warning', 'Quantity must be a whole number.');
                }

                item.quantity = Math.max(whole, 1);
            },

            get subTotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },

            get discountAmount() {
                const discountValue = Number(this.discount_value || 0);
                if (!Number.isFinite(discountValue) || discountValue <= 0) {
                    return 0;
                }

                if (this.discount_type === 'percentage') {
                    const percent = Math.min(discountValue, 100);
                    return (this.subTotal * percent) / 100;
                }

                return Math.min(discountValue, this.subTotal);
            },

            get netTotal() {
                return Math.max(this.subTotal - this.discountAmount, 0);
            },

            get totalPaid() {
                return this.payments.reduce((sum, payment) => {
                    const value = Number(payment.amount || 0);
                    return sum + (Number.isFinite(value) ? Math.max(value, 0) : 0);
                }, 0);
            },

            get balanceDue() {
                return Math.max(this.netTotal - this.totalPaid, 0);
            },

            submitForm(event) {
                if (!this.selectedSupplier || !this.selectedSupplier.id) {
                    this.notify('warning', 'Please select a supplier.');
                    return;
                }

                const hasInvalidItem = this.items.some((item) => {
                    const qty = Number(item.quantity || 0);
                    const price = Number(item.purchase_price || 0);
                    return !item.product_name
                        || (!item.product_variant_id && !item.product_id)
                        || !Number.isFinite(qty)
                        || !Number.isInteger(qty)
                        || qty <= 0
                        || !Number.isFinite(price)
                        || price < 0;
                });

                if (hasInvalidItem) {
                    this.notify('warning', 'Please select a valid product and enter quantity and unit price for each item.');
                    return;
                }

                const selectedKeys = new Set();
                const hasDuplicateItems = this.items.some((item) => {
                    const key = item.product_variant_id
                        ? `variant:${item.product_variant_id}`
                        : (item.product_id ? `product:${item.product_id}` : null);

                    if (!key) {
                        return false;
                    }

                    if (selectedKeys.has(key)) {
                        return true;
                    }

                    selectedKeys.add(key);
                    return false;
                });

                if (hasDuplicateItems) {
                    this.notify('warning', 'The same product variant cannot be added more than once in a single purchase.');
                    return;
                }

                const hasInvalidPayment = this.payments.some((payment) => {
                    const amount = Number(payment.amount || 0);
                    return !Number.isFinite(amount) || amount <= 0;
                });

                if (hasInvalidPayment) {
                    this.notify('warning', 'Each payment row must have an amount greater than zero.');
                    return;
                }

                event.target.submit();
            },
        };
    }
</script>
