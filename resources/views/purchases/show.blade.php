<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Purchase: {{ $purchase->purchase_number }}</h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="me-2.5 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <a href="{{ route('purchases.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">Purchases</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400">Details</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    @php
        $balance = (float) $purchase->net_total - (float) $purchase->paid_amount;
        $totalItemCount = (int) $purchase->items->count();
        $totalQuantityCount = (int) $purchase->items->sum('quantity');
        $statusStyles = [
            'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
            'checking' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
            'verified' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'complete' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ];
        $paymentStatusStyles = [
            'due' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ];
    @endphp

    <x-form-layout>
        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @if(($purchase->status ?? 'pending') !== 'complete')
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                Inventory is still pending for this purchase. Stock will be added only when the purchase status is moved to <span class="font-semibold">Complete</span>.
            </div>
        @else
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                Inventory was added to stock when this purchase was completed on {{ optional($purchase->stock_applied_at)->format('d M Y h:i A') ?: optional($purchase->completed_at)->format('d M Y h:i A') ?: '-' }}. Completed purchases are locked from further editing.
            </div>
        @endif

        <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
            @if(($purchase->status ?? 'pending') !== 'complete')
                <a href="{{ route('purchases.edit', $purchase) }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit
                </a>
            @else
                <span class="inline-flex items-center rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 016 0v2H9z"></path></svg>
                    Editing Locked
                </span>
            @endif
            <a href="{{ route('purchases.barcodes', $purchase) }}" target="_blank" class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 focus:ring-4 focus:ring-blue-200 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/30">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7V4m0 3h16M4 7v13m0-13h16m0 0V4m0 3v13M9 11h6m-6 4h6"></path></svg>
                Print All Barcodes
            </a>
            <a href="{{ route('purchases.pdf', $purchase) }}" target="_blank" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path></svg>
                Print / PDF
            </a>
        </div>

        <x-form-section title="Purchase Summary">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Purchasing ID</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $purchase->purchase_number }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ optional($purchase->purchase_date)->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Supplier</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $purchase->supplier->business_name ?? $purchase->supplier->name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                    <span class="mt-1 inline-flex rounded px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$purchase->status ?? 'pending'] ?? $statusStyles['pending'] }}">
                        {{ ucfirst($purchase->status ?? 'pending') }}
                    </span>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment Status</p>
                    <span class="mt-1 inline-flex rounded px-2.5 py-0.5 text-xs font-medium {{ $paymentStatusStyles[$purchase->payment_status] ?? $paymentStatusStyles['due'] }}">
                        {{ ucfirst($purchase->payment_status) }}
                    </span>
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Workflow & Counts">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Item Count</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalItemCount }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Qty Count</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalQuantityCount }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Created By</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $purchase->creator?->name ?? '-' }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ optional($purchase->created_at)->format('d M Y h:i A') ?: '-' }}</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Checking By</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $purchase->checker?->name ?? '-' }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ optional($purchase->checked_at)->format('d M Y h:i A') ?: '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Verified By</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $purchase->verifier?->name ?? '-' }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ optional($purchase->verified_at)->format('d M Y h:i A') ?: '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Complete By</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $purchase->completer?->name ?? '-' }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ optional($purchase->completed_at)->format('d M Y h:i A') ?: '-' }}</p>
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Purchase Items">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Product Name & Variant</th>
                            <th class="px-6 py-3">SKU</th>
                            <th class="px-6 py-3">Tracked Labels</th>
                            <th class="px-6 py-3 text-right">PCS Quantity</th>
                            <th class="px-6 py-3 text-right">Unit Price</th>
                            <th class="px-6 py-3 text-right">Line Total</th>
                            <th class="px-6 py-3 text-center">Barcode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $index => $item)
                            <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                <td class="px-6 py-4">{{ $index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if($item->variant)
                                            {{ $item->variant->unit_value ? $item->variant->unit_value . ' ' : '' }}{{ $item->variant->unit->name ?? ($item->variant->unit->short_name ?? '-') }}
                                        @else
                                            Variant not linked
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-600 dark:text-gray-300">
                                    {{ $item->variant?->sku ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->inventoryUnits->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($item->inventoryUnits as $trackedUnit)
                                                @php
                                                    $trackedStatus = strtolower((string) $trackedUnit->status);
                                                    $trackedStatusClass = match ($trackedStatus) {
                                                        'available' => 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300',
                                                        'pending_receipt' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300',
                                                        'allocated' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300',
                                                        'delivered' => 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900/40 dark:bg-purple-900/20 dark:text-purple-300',
                                                        default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex rounded-lg border px-2 py-1 font-mono text-[11px] {{ $trackedStatusClass }}" title="{{ ucfirst(str_replace('_', ' ', $trackedUnit->status)) }}">
                                                    {{ $trackedUnit->unit_code }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">No labels generated</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">{{ number_format((float) $item->quantity, 0) }}</td>
                                <td class="px-6 py-4 text-right">Rs. {{ number_format((float) $item->purchase_price, 2) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Rs. {{ number_format((float) $item->total, 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($item->variant)
                                        <a href="{{ route('purchases.items.barcodes', [$purchase, $item]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/30" title="Print {{ number_format((float) $item->quantity, 0) }} labels">
                                            Print {{ number_format((float) $item->quantity, 0) }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-form-section>

        <x-form-section title="Totals">
            <div class="ml-auto w-full space-y-2 md:w-96">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                    <span class="font-semibold text-gray-900 dark:text-white">Rs. {{ number_format((float) $purchase->sub_total, 2) }}</span>
                </div>
                @if((float) $purchase->discount_amount > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">Discount ({{ $purchase->discount_type === 'percentage' ? number_format((float) $purchase->discount_value, 2) . '%' : 'Fixed' }})</span>
                        <span class="font-semibold text-green-700 dark:text-green-300">- Rs. {{ number_format((float) $purchase->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-sm dark:border-gray-700">
                    <span class="font-medium text-gray-700 dark:text-gray-200">Net Total</span>
                    <span class="text-base font-bold text-blue-700 dark:text-blue-300">Rs. {{ number_format((float) $purchase->net_total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Paid Amount</span>
                    <span class="font-semibold text-gray-900 dark:text-white">Rs. {{ number_format((float) $purchase->paid_amount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">Balance Due</span>
                    <span class="font-semibold {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">Rs. {{ number_format(max($balance, 0), 2) }}</span>
                </div>
            </div>
        </x-form-section>

        @if(is_array($purchase->payments_data) && count($purchase->payments_data) > 0)
            <x-form-section title="Payment Entries">
                <div class="space-y-3">
                    @foreach($purchase->payments_data as $index => $payment)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment #{{ $index + 1 }}</p>
                                    <p class="mt-1 font-semibold text-green-700 dark:text-green-300">Rs. {{ number_format((float) ($payment['amount'] ?? 0), 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Method</p>
                                    <p class="mt-1 text-gray-900 dark:text-white">{{ $payment['method'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</p>
                                    <p class="mt-1 text-gray-900 dark:text-white">{{ !empty($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('d M Y') : '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Account</p>
                                    <p class="mt-1 text-gray-900 dark:text-white">{{ $payment['account'] ?: '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Note</p>
                                    <p class="mt-1 text-gray-900 dark:text-white">{{ $payment['note'] ?: '-' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-form-section>
        @endif
    </x-form-layout>
</x-app-layout>
