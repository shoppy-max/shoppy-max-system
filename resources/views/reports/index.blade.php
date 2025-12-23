<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports & Analytics') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Total Delivered Sales</h3>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($totalSales, 2) }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Today's Sales</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($todaySales, 2) }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Pending Orders</h3>
                    <p class="text-2xl font-bold text-yellow-600">{{ $pendingOrders }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Total Orders</h3>
                    <p class="text-2xl font-bold">{{ $totalOrders }}</p>
                </div>
            </div>

            <!-- Report Links Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                
                <a href="{{ route('reports.province') }}" class="block p-6 bg-white rounded-lg border hover:bg-gray-50 transition">
                    <div class="flex items-center mb-2">
                        <div class="p-2 bg-blue-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">Province Wise Sales</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Geographical breakdown of sales performance.</p>
                </a>

                <a href="{{ route('reports.profit-loss') }}" class="block p-6 bg-white rounded-lg border hover:bg-gray-50 transition">
                    <div class="flex items-center mb-2">
                        <div class="p-2 bg-green-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">Profit & Loss</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Financial analysis of sales vs COGS and logistics.</p>
                </a>

                <a href="{{ route('reports.stock') }}" class="block p-6 bg-white rounded-lg border hover:bg-gray-50 transition">
                    <div class="flex items-center mb-2">
                        <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">Stock Report</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Inventory valuation and batch aging.</p>
                </a>

                <a href="{{ route('reports.product-sales') }}" class="block p-6 bg-white rounded-lg border hover:bg-gray-50 transition">
                    <div class="flex items-center mb-2">
                        <div class="p-2 bg-indigo-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">Product Sales</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Detailed product performance metrics.</p>
                </a>

                <a href="{{ route('reports.packet-count') }}" class="block p-6 bg-white rounded-lg border hover:bg-gray-50 transition">
                    <div class="flex items-center mb-2">
                        <div class="p-2 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">Packet Count (User)</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Packing performance by staff member.</p>
                </a>

                <a href="{{ route('reports.user-sales') }}" class="block p-6 bg-white rounded-lg border hover:bg-gray-50 transition">
                    <div class="flex items-center mb-2">
                        <div class="p-2 bg-red-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">User Wise Sales</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Sales contribution by user/reseller.</p>
                </a>

            </div>
            
            <!-- Simple Chart Placeholder -->
            <div class="mt-8 bg-white p-6 rounded-lg shadow">
                <h3 class="font-bold mb-4">Monthly Sales Trend</h3>
                <div class="space-y-4">
                    @foreach($monthlySales as $data)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>{{ $data->month }}</span>
                            <span class="font-bold">{{ number_format($data->sums, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ ($data->sums / ($monthlySales->max('sums') ?: 1)) * 100 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
