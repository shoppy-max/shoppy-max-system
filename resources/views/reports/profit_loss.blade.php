<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profit & Loss Statement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900">
                    
                    <form method="GET" class="mb-8 flex gap-4 items-end no-print">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-500">Start Date</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="border rounded px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-500">End Date</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="border rounded px-2 py-1">
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded font-bold">Filter</button>
                    </form>

                    <h3 class="text-center font-bold text-2xl mb-2">Income Statement</h3>
                    <p class="text-center text-gray-500 mb-8">
                        @if(request('start_date'))
                            Period: {{ request('start_date') }} to {{ request('end_date') ?? 'Now' }}
                        @else
                            All Time
                        @endif
                    </p>

                    <div class="space-y-4">
                        <!-- Revenue -->
                        <div class="flex justify-between items-center border-b pb-2">
                            <span class="font-bold text-lg">Total Revenue Sales</span>
                            <span class="font-bold text-lg">{{ number_format($data['total_sales'], 2) }}</span>
                        </div>

                        <!-- COGS -->
                        <div class="flex justify-between items-center text-red-600 pl-4">
                            <span>Cost of Goods Sold (FIFO)</span>
                            <span>- {{ number_format($data['cogs'], 2) }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center border-t border-b py-2 bg-gray-50">
                            <span class="font-bold text-xl">Gross Profit</span>
                            <span class="font-bold text-xl {{ $data['gross_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($data['gross_profit'], 2) }}
                            </span>
                        </div>

                        <!-- Expenses -->
                        <h4 class="font-bold uppercase text-sm text-gray-500 mt-4">Logistics & Expenses</h4>
                        
                        <div class="flex justify-between items-center pl-4 text-red-600">
                            <span>Courier Costs (Paid)</span>
                            <span>- {{ number_format($data['courier_cost'], 2) }}</span>
                        </div>
                         <!-- Add Delivery Income back if it was tracked separate from Sales, but we assumed it in Sales. -->
                         <!-- If Delivery Fee is income, it's inside Total Sales usually, but nice to visualize. -->
                        <div class="flex justify-between items-center pl-4 text-gray-500">
                            <span>(Delivery Fees Collected)</span>
                            <span>{{ number_format($data['delivery_income'], 2) }}</span>
                        </div>

                        <!-- Net Profit -->
                        <div class="flex justify-between items-center border-t-2 border-black py-4 mt-4 bg-yellow-50">
                            <span class="font-bold text-2xl">NET PROFIT</span>
                            <span class="font-bold text-2xl {{ $data['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                {{ number_format($data['net_profit'], 2) }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
