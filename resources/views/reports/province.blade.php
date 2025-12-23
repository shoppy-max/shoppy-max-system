<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Province Wise Sales Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Table -->
                        <div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Province</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sales</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Count</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($provinceSales as $row)
                                        <tr>
                                            <td class="px-6 py-4 font-bold">{{ $row->province ?? 'Unassigned' }}</td>
                                            <td class="px-6 py-4">{{ number_format($row->total_sales, 2) }}</td>
                                            <td class="px-6 py-4">{{ $row->order_count }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">No data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Simple Chart Representation -->
                        <div class="p-4 border rounded bg-gray-50">
                            <h3 class="font-bold mb-4">Sales Distribution</h3>
                            <div class="space-y-4">
                                @php $maxSales = $provinceSales->max('total_sales') ?: 1; @endphp
                                @foreach($provinceSales as $row)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>{{ $row->province ?? 'Unassigned' }}</span>
                                        <span class="font-bold text-xs">{{ round(($row->total_sales / $maxSales) * 100) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ ($row->total_sales / $maxSales) * 100 }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
