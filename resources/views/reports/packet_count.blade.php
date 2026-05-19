<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Packed & Pick From Rack Report</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Counts by operator using packed and picked timestamps.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Reports</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1.2fr_1fr_1fr_auto_auto_auto] md:items-end">
                    <div>
                        <label for="user_id" class="mb-1 block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">User</label>
                        <select id="user_id" name="user_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">All users with activity</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
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
                    <a href="{{ route('reports.packet-count') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Reset</a>
                    @can('export reports')
                        <div class="flex gap-2">
                            <a href="{{ route('reports.packet-count', array_merge(request()->except(['page', 'export']), ['export' => 'pdf'])) }}" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">PDF</a>
                            <a href="{{ route('reports.packet-count', array_merge(request()->except(['page', 'export']), ['export' => 'excel'])) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Excel</a>
                        </div>
                    @endcan
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-purple-100 bg-purple-50 p-4 dark:border-purple-900 dark:bg-purple-950/30">
                    <p class="text-xs font-semibold uppercase text-purple-700 dark:text-purple-300">Packed Count</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['packed_count']) }}</p>
                </div>
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                    <p class="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">Pick From Rack Count</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['picked_count']) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3 text-right">Packed Count</th>
                                <th class="px-4 py-3 text-right">Pick From Rack Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($paginatedRows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $row['user_name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['email'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['packed_count']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['picked_count']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No packing activity found for the selected filters.</td>
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
