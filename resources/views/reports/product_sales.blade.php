<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Product Sales Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <h3 class="font-bold mb-4">Top Selling Products</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Qty Sold</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Revenue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Breakdown</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($productSales as $row)
                                <tr>
                                    <td class="px-6 py-4 font-bold">{{ $row->product_name }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-500">{{ $row->sku }}</td>
                                    <td class="px-6 py-4 font-bold">{{ $row->total_qty }}</td>
                                    <td class="px-6 py-4 text-green-600">{{ number_format($row->total_revenue, 2) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ ucfirst($row->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No product sales found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
