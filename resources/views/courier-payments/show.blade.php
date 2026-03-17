<x-app-layout>
    @php
        $totalRealDelivery = 0;
        $totalCommission = 0;
        $totalReceived = 0;
        foreach ($courierPayment->orders as $order) {
            $systemCharge = \App\Support\CourierSettlement::systemDeliveryCharge($order);
            $realCharge = \App\Support\CourierSettlement::realDeliveryCharge($order);
            $commission = \App\Support\CourierSettlement::courierCommission($order, $realCharge);
            $received = \App\Support\CourierSettlement::receivedAmount($order, $realCharge);
            $totalRealDelivery += $realCharge;
            $totalCommission += $commission;
            $totalReceived += $received;
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Courier Payment Details</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review the reconciled orders and the received amount breakdown for this courier settlement.</p>
            </div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">Dashboard</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('courier-payments.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Courier Payments</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Details</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="space-y-6 p-6">
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div class="xl:col-span-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Courier</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $courierPayment->courier->name }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $courierPayment->payment_date->format('Y-m-d') }}</p>
            </div>
            <div class="xl:col-span-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-700/60 dark:bg-emerald-900/20">
                <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Received Amount</p>
                <p class="mt-1 text-lg font-semibold text-emerald-900 dark:text-emerald-100">LKR {{ number_format($courierPayment->amount, 2) }}</p>
            </div>
            <div class="xl:col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Linked Orders</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $courierPayment->orders->count() }}</p>
            </div>
            <div class="xl:col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Real Delivery Total</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">LKR {{ number_format($totalRealDelivery, 2) }}</p>
            </div>
            <div class="xl:col-span-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Courier Commission</p>
                <p class="mt-1 text-lg font-semibold {{ $totalCommission < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">LKR {{ number_format($totalCommission, 2) }}</p>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reconciled Orders</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">These orders were settled under this courier payment. Delivered status is applied when the payment is reconciled.</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Waybill Number</th>
                            <th class="px-4 py-3">Order No</th>
                            <th class="px-4 py-3">Payment Method</th>
                            <th class="px-4 py-3 text-right">Order Amount</th>
                            <th class="px-4 py-3 text-right">System Delivery Charge</th>
                            <th class="px-4 py-3 text-right">Real Delivery Charge</th>
                            <th class="px-4 py-3 text-right">Courier Commission</th>
                            <th class="px-4 py-3 text-right">Received Amount</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($courierPayment->orders as $order)
                            @php
                                $systemCharge = \App\Support\CourierSettlement::systemDeliveryCharge($order);
                                $realCharge = \App\Support\CourierSettlement::realDeliveryCharge($order);
                                $commission = \App\Support\CourierSettlement::courierCommission($order, $realCharge);
                                $received = \App\Support\CourierSettlement::receivedAmount($order, $realCharge);
                            @endphp
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $order->waybill_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">{{ $order->order_number ?: ('Order #' . $order->id) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">{{ $order->payment_method ?: '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ number_format($systemCharge, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ number_format($realCharge, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium {{ $commission < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-200' }}">{{ number_format($commission, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($received, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center rounded-lg bg-primary-50 px-3 py-2 text-xs font-medium text-primary-700 transition hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50">
                                        View Order
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No orders are linked to this courier payment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
