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
    @endphp

    <x-form-layout>
        <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('purchases.edit', $purchase) }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit
            </a>
            <a href="{{ route('purchases.pdf', $purchase) }}" target="_blank" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path></svg>
                Print / PDF
            </a>
        </div>

        <x-form-section title="Purchase Summary">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</p>
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
                    @if($balance <= 0)
                        <span class="mt-1 inline-flex rounded bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
                    @elseif($purchase->paid_amount > 0)
                        <span class="mt-1 inline-flex rounded bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Partial</span>
                    @else
                        <span class="mt-1 inline-flex rounded bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Due</span>
                    @endif
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Items">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3 text-right">Qty</th>
                            <th class="px-6 py-3 text-right">Unit Price</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $index => $item)
                            <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                <td class="px-6 py-4">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</td>
                                <td class="px-6 py-4 text-right">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $item->purchase_price, 2) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format((float) $item->total, 2) }}</td>
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
