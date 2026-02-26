<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between no-print">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Order Details') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back
                </a>
                <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit
                </a>
                <a href="{{ route('orders.pdf', $order) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Download PDF
                </a>
                <a href="{{ route('orders.print', $order) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Print
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg print:shadow-none print:border-none">
                <!-- Invoice Header -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">INVOICE</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Order #{{ $order->order_number }}</p>
                            <div class="mt-2">
                                @php
                                    $colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirm' => 'bg-green-100 text-green-800',
                                        'hold' => 'bg-amber-100 text-amber-800',
                                        'cancel' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="{{ $colors[$order->status] ?? 'bg-gray-100 text-gray-800' }} text-xs font-semibold px-2.5 py-0.5 rounded uppercase tracking-wide">
                                    {{ $order->status }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                             <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ config('app.name', 'ShoppyMax') }}</h2>
                             <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Date: {{ optional($order->order_date)->format('d M, Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Customer Info -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Invoice To:</h3>
                        <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $order->customer->name ?? $order->customer_name }}</p>
                        <p class="text-gray-600 dark:text-gray-400">{{ $order->customer->address ?? $order->customer_address }}</p>
                        <p class="text-gray-600 dark:text-gray-400">{{ $order->customer->mobile ?? $order->customer_phone }}</p>
                        <p class="text-gray-600 dark:text-gray-400">
                             {{ $order->customer_city ?? $order->customer->city }}{{ $order->customer_district ? ', ' . $order->customer_district : '' }}{{ $order->customer_province ? ', ' . $order->customer_province : '' }}
                        </p>
                    </div>

                    <!-- Order Details (Reseller context if any) -->
                    <div>
                        @if($order->order_type === 'reseller' && $order->reseller)
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Reseller Info:</h3>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $order->reseller->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $order->reseller->business_name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Mobile: {{ $order->reseller->mobile }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Account: {{ $order->reseller->reseller_type === 'direct_reseller' ? 'Direct Reseller' : 'Reseller' }}</p>
                        @else
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Order Info:</h3>
                             <p class="text-sm text-gray-600 dark:text-gray-400">Type: <span class="uppercase font-semibold">{{ $order->order_type }}</span></p>
                             <p class="text-sm text-gray-600 dark:text-gray-400">Pay Method: <span class="font-medium">{{ $order->payment_method === 'COD' ? 'Cash on Delivery (COD)' : $order->payment_method }}</span></p>
                             @if($order->courier)
                                <p class="text-sm text-gray-600 dark:text-gray-400">Courier: {{ $order->courier->name }}</p>
                             @endif
                             <p class="text-sm text-gray-600 dark:text-gray-400">Call Status: <span class="capitalize">{{ $order->call_status }}</span></p>
                             <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Created By: {{ $order->user->name ?? 'System' }}</p>
                        @endif
                    </div>
                </div>

                <!-- Items Table -->
                <div class="px-8 pb-8">
                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qty</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Unit Price</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $item->product_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                            {{ $item->sku }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                            {{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                                            {{ number_format($item->subtotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Totals -->
                <div class="px-8 pb-8 flex justify-end">
                    <div class="w-full sm:w-1/2 lg:w-1/3">
                        @php
                            $paidAmount = (float) ($order->paid_amount ?? 0);
                            $remainingAmount = max((float) $order->total_amount - $paidAmount, 0);
                            $discountAmount = (float) ($order->discount_amount ?? 0);
                            $subTotalBeforeDiscount = max(((float) $order->total_amount - (float) $order->courier_charge) + $discountAmount, 0);
                        @endphp
                        <div class="space-y-2">
                             @if($order->order_type === 'reseller')
                                <div class="flex justify-between items-center text-sm text-purple-600 dark:text-purple-400 py-1 border-b dark:border-gray-700">
                                    <span>Total Commission Paid</span>
                                    <span>LKR {{ number_format($order->total_commission, 2) }}</span>
                                </div>
                             @endif
                            
                             <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 py-1">
                                <span>Subtotal</span>
                                <span>LKR {{ number_format($subTotalBeforeDiscount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 py-1">
                                <span>Discount</span>
                                <span>- LKR {{ number_format($discountAmount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 py-1 border-b dark:border-gray-700">
                                <span>Courier Charge</span>
                                <span>LKR {{ number_format($order->courier_charge, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 py-1">
                                <span>Paid Amount</span>
                                <span>LKR {{ number_format($paidAmount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 py-1 border-b dark:border-gray-700">
                                <span>{{ $order->payment_method === 'COD' ? 'Remaining (COD Collect)' : 'Remaining Amount' }}</span>
                                <span>LKR {{ number_format($remainingAmount, 2) }}</span>
                            </div>
                             
                             <div class="flex justify-between items-center text-lg font-bold text-gray-900 dark:text-white pt-2">
                                <span>Grand Total</span>
                                <span>LKR {{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if(is_array($order->payments_data) && count($order->payments_data) > 0)
                <div class="px-8 pb-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Payment Entries</p>
                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Note</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($order->payments_data as $payment)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $payment['date'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-white">LKR {{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $payment['note'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($order->sales_note)
                <div class="px-8 pb-4">
                     <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Sales Note / Remarks:</p>
                     <p class="text-sm text-gray-700 dark:text-gray-300 bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded border border-yellow-100 dark:border-yellow-900/30">
                         {{ $order->sales_note }}
                     </p>
                </div>
                @endif

                <div class="p-8 pt-12 mt-8 border-t border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500">
                        <p>Thank you for your business!</p>
                        <p class="mt-1 text-xs">This is a system generated invoice. No signature is required.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12pt; }
            .print\:shadow-none { box-shadow: none !important; }
            .print\:border-none { border: none !important; }
        }
    </style>
</x-app-layout>
