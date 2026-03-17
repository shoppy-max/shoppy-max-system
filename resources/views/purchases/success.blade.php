@php
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

<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6 text-center text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h2 class="mb-2 text-3xl font-bold">Purchase Created Successfully!</h2>
                    <p class="mb-8 text-lg text-gray-600 dark:text-gray-400">
                        Purchasing ID <strong class="text-gray-900 dark:text-white">{{ $purchase->purchase_number }}</strong> has been recorded for
                        <strong class="text-gray-900 dark:text-white">{{ $purchase->supplier->business_name ?? $purchase->supplier->name }}</strong>.
                    </p>

                    <div class="mb-8 grid grid-cols-1 gap-4 text-left sm:grid-cols-2 xl:grid-cols-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Purchasing ID</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $purchase->purchase_number }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Purchase Date</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ optional($purchase->purchase_date)->format('d M Y') }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $purchase->items->count() }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">PCS Qty</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format((float) $purchase->items->sum('quantity'), 0) }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                            <span class="mt-1 inline-flex rounded px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$purchase->status ?? 'pending'] ?? $statusStyles['pending'] }}">
                                {{ ucfirst($purchase->status ?? 'pending') }}
                            </span>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment Status</p>
                            <span class="mt-1 inline-flex rounded px-2.5 py-0.5 text-xs font-medium {{ $paymentStatusStyles[$purchase->payment_status] ?? $paymentStatusStyles['due'] }}">
                                {{ ucfirst($purchase->payment_status) }}
                            </span>
                        </div>
                    </div>

                    @if(($purchase->status ?? 'pending') !== 'complete')
                        <div class="mb-8 rounded-lg border border-blue-200 bg-blue-50 p-4 text-left text-sm text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                            Inventory has not been added yet. Stock will update only when this purchase is moved to <span class="font-semibold">Complete</span>.
                        </div>
                    @else
                        <div class="mb-8 rounded-lg border border-green-200 bg-green-50 p-4 text-left text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                            Inventory was added to stock when this purchase was completed on {{ optional($purchase->stock_applied_at)->format('d M Y h:i A') ?: optional($purchase->completed_at)->format('d M Y h:i A') ?: '-' }}. Completed purchases stay locked to preserve audit accuracy.
                        </div>
                    @endif

                    <div class="mb-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('purchases.barcodes', $purchase) }}" target="_blank" class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7V4m0 3h16M4 7v13m0-13h16m0 0V4m0 3v13M9 11h6m-6 4h6"></path></svg>
                            Print All Barcodes
                        </a>
                        <a href="{{ route('purchases.show', $purchase) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            View Purchase
                        </a>
                    </div>

                    <h3 class="mb-4 border-b border-gray-200 pb-2 text-left text-xl font-semibold dark:border-gray-700">Purchased Items</h3>

                    <div class="relative mb-8 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Product</th>
                                    <th class="px-6 py-3">Variant</th>
                                    <th class="px-6 py-3">SKU</th>
                                    <th class="px-6 py-3">Tracked Labels</th>
                                    <th class="px-6 py-3 text-right">Qty</th>
                                    <th class="px-6 py-3 text-right">Unit Price</th>
                                    <th class="px-6 py-3 text-right">Line Total</th>
                                    <th class="px-6 py-3 text-center">Barcode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                    <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800 last:border-b-0">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</td>
                                        <td class="px-6 py-4">
                                            @if($item->variant)
                                                {{ $item->variant->unit_value ? $item->variant->unit_value . ' ' : '' }}{{ $item->variant->unit->name ?? ($item->variant->unit->short_name ?? '-') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs">{{ $item->variant?->sku ?? '-' }}</td>
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
                                        <td class="px-6 py-4 text-right">{{ $item->quantity }}</td>
                                        <td class="px-6 py-4 text-right">Rs. {{ number_format((float) $item->purchase_price, 2) }}</td>
                                        <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Rs. {{ number_format((float) $item->total, 2) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            @if($item->variant)
                                                <a href="{{ route('purchases.items.barcodes', [$purchase, $item]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-xs font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700" title="Print {{ number_format((float) $item->quantity, 0) }} labels">
                                                    Print {{ number_format((float) $item->quantity, 0) }} Labels
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

                    <div class="flex flex-col justify-center gap-4 sm:flex-row">
                        <a href="{{ route('purchases.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            Back to Purchase List
                        </a>
                        <a href="{{ route('purchases.create') }}" class="rounded-lg bg-green-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700">
                            Add Another Purchase
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
