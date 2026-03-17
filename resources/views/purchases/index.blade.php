<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{{ __('Purchases') }}</h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="me-2.5 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400">Purchases</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="space-y-6 p-6">
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-stats-card title="Total Purchases" :value="$totalPurchases" color="blue">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
            </x-stats-card>

            <x-stats-card title="Total Spent" :value="'Rs. ' . number_format((float) $totalSpent, 2)" color="green">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"></path></svg>
            </x-stats-card>

            <x-stats-card title="Total Due" :value="'Rs. ' . number_format((float) $totalDue, 2)" color="red">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </x-stats-card>
        </div>

        <div class="rounded-md border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-4">
                <form method="GET" action="{{ route('purchases.index') }}" class="space-y-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                        <div class="relative xl:col-span-4">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/></svg>
                            </div>
                            <input type="search" name="search" value="{{ request('search') }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Search by purchasing ID or supplier">
                        </div>

                        <div class="xl:col-span-2">
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" title="From date">
                        </div>

                        <div class="xl:col-span-2">
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" title="To date">
                        </div>

                        <div class="xl:col-span-2">
                            <select name="status" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">All Statuses</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                <option value="checking" @selected(request('status') === 'checking')>Checking</option>
                                <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                                <option value="complete" @selected(request('status') === 'complete')>Complete</option>
                            </select>
                        </div>

                        <div class="xl:col-span-2">
                            <select name="payment_status" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">All Payment Statuses</option>
                                <option value="due" @selected(request('payment_status') === 'due')>Due</option>
                                <option value="partial" @selected(request('payment_status') === 'partial')>Partial</option>
                                <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-200 pt-3 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                Apply
                            </button>
                            @if(request()->filled('search') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('status') || request()->filled('payment_status'))
                                <a href="{{ route('purchases.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    Clear
                                </a>
                            @endif
                        </div>

                        <a href="{{ route('purchases.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            New Purchase
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="w-12 px-3 py-3 text-center"></th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Purchasing ID</th>
                        <th class="px-6 py-3">Supplier</th>
                        <th class="px-6 py-3 text-right">Items</th>
                        <th class="px-6 py-3 text-right">Net Total</th>
                        <th class="px-6 py-3 text-right">Paid</th>
                        <th class="px-6 py-3 text-right">Balance</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Payment Status</th>
                        <th class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                @forelse ($purchases as $purchase)
                    <tbody x-data="{ open: false }" class="divide-y divide-gray-100 dark:divide-gray-700">
                        @php
                            $balance = (float) $purchase->net_total - (float) $purchase->paid_amount;
                        @endphp
                        <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700/50">
                            <td class="px-3 py-4 text-center">
                                <button type="button" @click="open = !open" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-blue-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-300" :title="open ? 'Hide item details' : 'Show item details'">
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </td>
                            <td class="px-6 py-4">{{ optional($purchase->purchase_date)->format('d M Y') }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $purchase->purchase_number }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $purchase->supplier->business_name ?? $purchase->supplier->name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->supplier->mobile ?? $purchase->supplier->phone ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">{{ $purchase->items_count }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format((float) $purchase->net_total, 2) }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format((float) $purchase->paid_amount, 2) }}</td>
                            <td class="px-6 py-4 text-right {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ number_format(max($balance, 0), 2) }}</td>
                            <td class="px-6 py-4 text-center">
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
                                <span class="rounded px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$purchase->status ?? 'pending'] ?? $statusStyles['pending'] }}">
                                    {{ ucfirst($purchase->status ?? 'pending') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="rounded px-2.5 py-0.5 text-xs font-medium {{ $paymentStatusStyles[$purchase->payment_status] ?? $paymentStatusStyles['due'] }}">
                                    {{ ucfirst($purchase->payment_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="rounded-lg p-2 text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-gray-700" title="View">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <a href="{{ route('purchases.barcodes', $purchase) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg p-2 text-sky-600 hover:bg-sky-100 dark:text-sky-400 dark:hover:bg-gray-700" title="Print All Barcodes">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    </a>
                                    @if(($purchase->status ?? 'pending') !== 'complete')
                                        <a href="{{ route('purchases.edit', $purchase) }}" class="rounded-lg p-2 text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-gray-700" title="Edit">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                    @else
                                        <span class="rounded-lg p-2 text-gray-400 dark:text-gray-500" title="Editing locked after completion">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 016 0v2H9z"></path></svg>
                                        </span>
                                    @endif
                                    @if(($purchase->status ?? 'pending') !== 'complete')
                                        <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="inline" onsubmit="return confirm('Delete this purchase?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-gray-700" title="Delete">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="rounded-lg p-2 text-gray-400 dark:text-gray-500" title="Deletion locked after completion">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 016 0v2H9z"></path></svg>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr x-show="open" x-transition.opacity style="display: none;" class="bg-gray-50/80 dark:bg-gray-900/30">
                            <td colspan="11" class="px-6 py-5">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Purchase Item Details</h4>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Expanded view for products, variants, SKU, quantity, unit price, and line totals.</p>
                                        </div>
                                        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                            {{ $purchase->items_count }} item{{ $purchase->items_count === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                                            <thead class="bg-gray-100 uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                <tr>
                                                    <th class="px-4 py-3">Product Name & Variant</th>
                                                    <th class="px-4 py-3">SKU</th>
                                                    <th class="px-4 py-3">Tracked Labels</th>
                                                    <th class="px-4 py-3 text-right">PCS Quantity</th>
                                                    <th class="px-4 py-3 text-right">Unit Price</th>
                                                    <th class="px-4 py-3 text-right">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($purchase->items as $item)
                                                    <tr class="border-t border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                                                        <td class="px-4 py-3">
                                                            <div class="font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</div>
                                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                                @if($item->variant)
                                                                    {{ $item->variant->unit_value ? $item->variant->unit_value . ' ' : '' }}{{ $item->variant->unit->name ?? ($item->variant->unit->short_name ?? '-') }}
                                                                @else
                                                                    Variant not linked
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 font-mono text-[11px]">{{ $item->variant?->sku ?? '-' }}</td>
                                                        <td class="px-4 py-3">
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
                                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ number_format((float) $item->quantity, 0) }}</td>
                                                        <td class="px-4 py-3 text-right">Rs. {{ number_format((float) $item->purchase_price, 2) }}</td>
                                                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Rs. {{ number_format((float) $item->total, 2) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No purchase items found.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="11" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">No purchases found for current filters.</td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div>
            {{ $purchases->withQueryString()->links() }}
        </div>
    </div>
</x-app-layout>
