<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Stock Movement</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $variant->product?->name }} / {{ $variant->sku }} / {{ trim(($variant->unit_value ?? '').' '.($variant->unit?->short_name ?? $variant->unit?->name ?? '')) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.stock') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Back</a>
                <a href="{{ route('reports.stock.show', array_merge(['variant' => $variant->id], request()->except(['page', 'export']), ['export' => 'pdf'])) }}" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">PDF</a>
                <a href="{{ route('reports.stock.show', array_merge(['variant' => $variant->id], request()->except(['page', 'export']), ['export' => 'excel'])) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Excel</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_1fr_auto_auto] md:items-end">
                    <div>
                        <label for="type" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Type</label>
                        <select id="type" name="type" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">All movements</option>
                            @foreach(['Purchasing', 'Sale', 'Cancel', 'Return'] as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reference" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Reference</label>
                        <input id="reference" name="reference" value="{{ request('reference') }}" type="search" placeholder="Order or purchase no" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="start_date" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Start date</label>
                        <input id="start_date" name="start_date" value="{{ request('start_date') }}" type="date" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label for="end_date" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">End date</label>
                        <input id="end_date" name="end_date" value="{{ request('end_date') }}" type="date" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>
                    <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
                    <a href="{{ route('reports.stock.show', $variant) }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Reset</a>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                    <p class="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">Total PCS Count</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_pcs']) }} PCS</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">Total Stock Value</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_value'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Filtered Movements</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['movement_count']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">In {{ number_format($summary['in_qty']) }} / Out {{ number_format($summary['out_qty']) }}</p>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                    <p class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">Filtered Value Change</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['net_value_change'] > 0 ? '+' : '' }}{{ number_format($summary['net_value_change'], 2) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed text-left text-xs lg:text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="w-[13%] px-3 py-3">Type</th>
                                <th class="w-[13%] px-3 py-3 text-right">Qty Change</th>
                                <th class="w-[13%] px-3 py-3 text-right">Available</th>
                                <th class="w-[15%] px-3 py-3">Date & Time</th>
                                <th class="w-[17%] px-3 py-3">Reference No</th>
                                <th class="w-[14%] px-3 py-3">Source</th>
                                <th class="w-[15%] px-3 py-3 text-right">Value Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($paginatedRows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                    <td class="px-3 py-3">
                                        <span class="inline-flex max-w-full rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['type'] === 'Sale' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $row['type'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold {{ $row['quantity_change'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $row['quantity_change'] > 0 ? '+' : '' }}{{ $row['quantity_change'] }}</td>
                                    <td class="px-3 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['available_quantity']) }}</td>
                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200">{{ $row['date_time'] }}</td>
                                    <td class="px-3 py-3">
                                        @if($row['reference_url'])
                                            <a href="{{ $row['reference_url'] }}" class="break-words font-mono text-xs font-semibold text-blue-700 hover:underline dark:text-blue-300">{{ $row['reference_no'] }}</a>
                                        @else
                                            <span class="break-words font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row['reference_no'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($row['reference_url'])
                                            <a href="{{ $row['reference_url'] }}" class="inline-flex max-w-full rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold leading-tight text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">
                                                View {{ $row['reference_type'] }}
                                            </a>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">Source unavailable</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold {{ $row['value_change'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $row['value_change'] > 0 ? '+' : '' }}{{ number_format($row['value_change'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No stock movement events found for the selected filters.</td>
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
