<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Stock Report</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Available SKU stock and FIFO value from tracked inventory units.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Reports</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_auto_auto] md:items-end">
                    <div>
                        <label for="search" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Product / SKU</label>
                        <input id="search" name="search" value="{{ request('search') }}" type="search" placeholder="Search product name or SKU" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
                    <a href="{{ route('reports.stock') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Reset</a>
                    <div class="flex gap-2">
                        <a href="{{ route('reports.stock', array_merge(request()->except(['page', 'export']), ['export' => 'pdf'])) }}" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">PDF</a>
                        <a href="{{ route('reports.stock', array_merge(request()->except(['page', 'export']), ['export' => 'excel'])) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Excel</a>
                    </div>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                    <p class="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">Total PCS Count</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_pcs']) }} PCS</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">Total Stock Value</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_value'], 2) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Product name, SKU, Variant</th>
                                <th class="px-4 py-3 text-right">Total Stock (Qty)</th>
                                <th class="px-4 py-3 text-right">Stock Value (FIFO)</th>
                                <th class="px-4 py-3 text-right">View</th>
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
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['stock_qty']) }} PCS</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($row['stock_value'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('reports.stock.show', $row['variant_id']) }}" class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No stock rows found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">
                    {{ $paginatedRows->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
