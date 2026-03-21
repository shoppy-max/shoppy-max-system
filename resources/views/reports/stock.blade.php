<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Stock Valuation Report') }}
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
                    <li class="inline-flex items-center">
                        <a href="{{ route('reports.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            Reports
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Stock</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800">

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="p-4 bg-blue-50 dark:bg-gray-700 rounded-lg border border-blue-100 dark:border-gray-600">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-300 uppercase">Total Inventory Value</span>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($products->sum('stock_value'), 2) }}</div>
            </div>
             <div class="p-4 bg-green-50 dark:bg-gray-700 rounded-lg border border-green-100 dark:border-gray-600">
                <span class="text-xs font-bold text-green-600 dark:text-green-300 uppercase">Total Items In Stock</span>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $products->sum('total_quantity') }}</div>
            </div>
        </div>

        <div class="relative overflow-x-auto sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Product</th>
                        <th scope="col" class="px-6 py-3 text-right">Total Stock (Qty)</th>
                        <th scope="col" class="px-6 py-3 text-right">Stock Value (FIFO)</th>
                        <th scope="col" class="px-6 py-3">Batch Breakdown</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white">{{ $product->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $product->category?->name }}</div>
                        </td>
                        <td class="px-6 py-4 font-bold text-right text-gray-900 dark:text-white">
                            {{ $product->total_quantity }}
                        </td>
                        <td class="px-6 py-4 font-bold text-right text-green-600 dark:text-green-400">
                            {{ number_format($product->stock_value, 2) }}
                        </td>
                        <td class="px-6 py-4 text-xs">
                             @if($product->purchaseItems->count() > 0)
                                <div class="space-y-1">
                                    @foreach($product->purchaseItems as $batch)
                                        @php
                                            $availableUnits = $batch->inventoryUnits->where('status', 'available')->count();
                                        @endphp
                                        @if($availableUnits > 0)
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300 font-mono">
                                                PO #{{ $batch->purchase->purchase_number ?? $batch->purchase_id }}
                                            </span>
                                            <span class="text-gray-900 dark:text-white font-semibold">
                                                {{ $availableUnits }} available
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">
                                                @ {{ number_format($batch->purchase_price ?? 0, 2) }}
                                            </span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 italic">No batch info</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                 <tfoot class="bg-gray-100 dark:bg-gray-700 font-bold text-gray-900 dark:text-white">
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-right">Total:</td>
                        <td class="px-6 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($products->sum('stock_value'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</x-app-layout>
