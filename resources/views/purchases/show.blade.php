<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Purchase Details: #{{ $purchase->purchase_number }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('purchases.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Purchases</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Details</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4 gap-2">
                 <a href="{{ route('purchases.edit', $purchase) }}" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Purchase
                </a>
                <button onclick="window.open('{{ route('purchases.pdf', $purchase) }}', '_blank').print();" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print
                </button>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800 p-6 print:shadow-none print:border print:border-gray-200">
                
                <!-- Status & Date Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Purchase Inovice</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Date: {{ $purchase->purchase_date->format('d M Y') }}</p>
                    </div>
                    <div class="mt-4 md:mt-0 text-right">
                        @php $balance = $purchase->net_total - $purchase->paid_amount; @endphp
                        @if($balance <= 0)
                            <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded dark:bg-green-900 dark:text-green-300">Paid</span>
                        @elseif($purchase->paid_amount > 0)
                            <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-1 rounded dark:bg-yellow-900 dark:text-yellow-300">Partial</span>
                        @else
                            <span class="bg-red-100 text-red-800 text-sm font-medium px-3 py-1 rounded dark:bg-red-900 dark:text-red-300">Unpaid</span>
                        @endif
                        <p class="text-xs text-gray-400 mt-1 uppercase tracking-wide font-semibold">{{ $purchase->purchase_number }}</p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- From: Supplier -->
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Supplier</h4>
                        <div class="text-gray-900 dark:text-white font-medium">
                            <p class="text-lg">{{ $purchase->supplier->business_name ?? $purchase->supplier->name }}</p>
                            <p class="text-sm text-gray-500">{{ $purchase->supplier->address }}</p>
                            <p class="text-sm text-gray-500">{{ $purchase->supplier->phone }}</p>
                            <p class="text-sm text-gray-500">{{ $purchase->supplier->email }}</p>
                        </div>
                    </div>
                    
                    <!-- To: Company (Static or Dynamic if configured) -->
                    <div class="md:text-right">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Billed To</h4>
                        <div class="text-gray-900 dark:text-white font-medium">
                            <p class="text-lg">{{ config('app.name', 'Company Name') }}</p>
                            <p class="text-sm text-gray-500">Inventory Department</p>
                        </div>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="relative overflow-x-auto border rounded-lg border-gray-200 dark:border-gray-700 mb-8">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">#</th>
                                <th scope="col" class="px-6 py-3">Product Description</th>
                                <th scope="col" class="px-6 py-3 text-right">Qty</th>
                                <th scope="col" class="px-6 py-3 text-right">Unit Price</th>
                                <th scope="col" class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $index => $item)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $item->product_name }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ number_format($item->purchase_price, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                    {{ number_format($item->total, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="flex justify-end">
                    <div class="w-full md:w-1/3 space-y-3 text-right">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span>{{ number_format($purchase->sub_total, 2) }}</span>
                        </div>
                        @if($purchase->discount_amount > 0)
                        <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                            <span>Discount ({{ $purchase->discount_type == 'percentage' ? $purchase->discount_value . '%' : 'Fixed' }})</span>
                            <span>- {{ number_format($purchase->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-3">
                            <span>Net Total</span>
                            <span>Rs. {{ number_format($purchase->net_total, 2) }}</span>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 pt-2">
                             <span>Paid Amount</span>
                             <span>{{ number_format($purchase->paid_amount, 2) }}</span>
                        </div>
                        
                        <div class="flex justify-between text-base font-semibold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }} border-t border-gray-200 dark:border-gray-700 pt-2">
                             <span>Balance Due</span>
                             <span>Rs. {{ number_format(max(0, $balance), 2) }}</span>
                        </div>
                    </div>
                </div>


                <!-- Payment Details Section -->
                @if($purchase->payments_data && is_array($purchase->payments_data) && count($purchase->payments_data) > 0)
                <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Payment Details</h4>
                    <div class="space-y-3">
                        @foreach($purchase->payments_data as $index => $payment)
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase block mb-1">Payment #{{ $index + 1 }}</span>
                                    <span class="font-bold text-green-700 dark:text-green-300">Rs. {{ number_format($payment['amount'] ?? 0, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase block mb-1">Method</span>
                                    <span class="text-gray-900 dark:text-white">{{ $payment['method'] ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase block mb-1">Date</span>
                                    <span class="text-gray-900 dark:text-white">{{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('d M Y') : 'N/A' }}</span>
                                </div>
                                @if(!empty($payment['account']))
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase block mb-1">Account</span>
                                    <span class="text-gray-900 dark:text-white">{{ $payment['account'] }}</span>
                                </div>
                                @endif
                                @if(!empty($payment['note']))
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase block mb-1">Note/Ref</span>
                                    <span class="text-gray-900 dark:text-white">{{ $payment['note'] }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
