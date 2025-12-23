<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Stock Valuation Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Stock (Qty)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Value (FIFO)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch Breakdown</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($products as $product)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-bold">{{ $product->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 font-bold">{{ $product->quantity }}</td>
                                <td class="px-6 py-4 font-bold text-green-600">{{ number_format($product->stock_value, 2) }}</td>
                                <td class="px-6 py-4 text-xs">
                                    @foreach($product->purchaseItems as $batch)
                                        <div class="mb-1 border-b pb-1 last:border-0">
                                            <span class="font-bold text-gray-700">Batch #{{ $batch->purchase->purchasing_number }}</span>: 
                                            {{ $batch->remaining_quantity }} left @ {{ number_format($batch->purchasing_price, 2) }}
                                            <span class="text-gray-400">({{ $batch->created_at->format('Y-m-d') }})</span>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-right">Total Inventory Value:</td>
                                <td class="px-6 py-3 text-green-700">{{ number_format($products->sum('stock_value'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
