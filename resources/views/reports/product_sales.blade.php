<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Product Wise Sale Report</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Cancelled orders are excluded from total PCS.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Reports</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label for="search" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Product / SKU</label>
                        <input id="search" name="search" value="{{ request('search') }}" type="search" placeholder="Search product name or SKU" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="start_date" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Start</label>
                        <input id="start_date" name="start_date" value="{{ request('start_date') }}" type="date" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="end_date" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">End</label>
                        <input id="end_date" name="end_date" value="{{ request('end_date') }}" type="date" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>
                <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[420px]">
                    <div>
                        <label for="min_return_percentage" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Min return %</label>
                        <input id="min_return_percentage" name="min_return_percentage" value="{{ request('min_return_percentage') }}" type="number" min="0" max="100" step="0.01" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="max_return_percentage" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Max return %</label>
                        <input id="max_return_percentage" name="max_return_percentage" value="{{ request('max_return_percentage') }}" type="number" min="0" max="100" step="0.01" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                    <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
                    <a href="{{ route('reports.product-sales') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Reset</a>
                    <a href="{{ route('reports.product-sales', array_merge(request()->except(['page', 'export']), ['export' => 'pdf'])) }}" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">PDF</a>
                    <a href="{{ route('reports.product-sales', array_merge(request()->except(['page', 'export']), ['export' => 'excel'])) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Excel</a>
                    </div>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total PCS</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_pcs']) }}</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">Delivered PCS</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['delivered_pcs']) }}</p>
                </div>
                <div class="rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                    <p class="text-xs font-semibold uppercase text-red-700 dark:text-red-300">Returned PCS</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['returned_pcs']) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[920px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Product Name / SKU / Variant</th>
                                <th class="px-4 py-3 text-right">Total PCS</th>
                                <th class="px-4 py-3 text-right">Delivered PCS</th>
                                <th class="px-4 py-3 text-right">Returned PCS</th>
                                <th class="px-4 py-3 text-right">Return % (PCS)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($paginatedRows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $row['product_name'] }}</div>
                                        <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                            <span class="rounded bg-gray-100 px-2 py-1 font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $row['sku'] }}</span>
                                            <span class="rounded bg-blue-50 px-2 py-1 text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ $row['variant_label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['total_pcs']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($row['delivered_pcs']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-red-700 dark:text-red-300">{{ number_format($row['returned_pcs']) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ number_format($row['return_percentage'], 2) }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No product sales found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">{{ $paginatedRows->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
