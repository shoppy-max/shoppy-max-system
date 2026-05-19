<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Waybill Excel Export') }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Waybill Excel Export</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="rounded-md bg-white p-6 shadow-md dark:bg-gray-800">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Courier</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Printed waybill orders are grouped by courier here. Open a courier to review pending exports, search, and download the current Excel sheet.</p>
            </div>
            <div class="w-full lg:w-80">
                <label for="courier_search" class="sr-only">Search Courier</label>
                <input
                    id="courier_search"
                    type="text"
                    oninput="filterCourierCards(this.value)"
                    placeholder="Search courier by name..."
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                >
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Active Couriers</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($couriers->count()) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pending Excel Export</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($couriers->sum('pending_export_count')) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Downloaded Excel Entries</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($couriers->sum('downloaded_export_count')) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Printed Waybill Orders</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($couriers->sum('printed_waybills_count')) }}</p>
            </div>
        </div>

        <div id="empty-search-state" class="col-span-full mb-4 hidden rounded-lg border border-dashed border-gray-300 p-10 text-center text-gray-500 dark:border-gray-600 dark:text-gray-400">
            No couriers match your search.
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" id="courier-card-grid">
            @forelse($couriers as $courier)
                @php
                    $canOpenExportQueue = (int) ($courier->printed_waybills_count ?? 0) > 0;
                @endphp
                @if($canOpenExportQueue)
                    @can('view waybill excel exports')
                    <a href="{{ route('orders.waybill-excel.show', $courier) }}" class="courier-card block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-700">
                    @else
                    <div class="courier-card block rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @endcan
                @else
                    <div class="courier-card block rounded-lg border border-gray-200 bg-gray-50 p-5 shadow-sm opacity-80 dark:border-gray-700 dark:bg-gray-800/70">
                @endif
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $courier->name }}</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $courier->phone ?: 'No phone available' }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $courier->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $courier->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Printed waybill orders</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($courier->printed_waybills_count ?? 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Pending export</span>
                            <span class="font-semibold {{ ($courier->pending_export_count ?? 0) > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">{{ number_format($courier->pending_export_count ?? 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Downloaded</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($courier->downloaded_export_count ?? 0) }}</span>
                        </div>
                    </div>

                    @if($canOpenExportQueue)
                        <p class="mt-3 text-xs font-medium text-primary-700 dark:text-primary-400">Open export list</p>
                    @else
                        <p class="mt-3 text-xs font-medium text-gray-600 dark:text-gray-400">No printed waybill orders yet for this courier.</p>
                    @endif
                @if($canOpenExportQueue)
                    @can('view waybill excel exports')
                    </a>
                    @else
                    </div>
                    @endcan
                @else
                    </div>
                @endif
            @empty
                <div class="col-span-full rounded-lg border border-dashed border-gray-300 p-10 text-center text-gray-500 dark:border-gray-600 dark:text-gray-400">
                    No active couriers found.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function filterCourierCards(searchValue) {
            const query = (searchValue || '').toLowerCase().trim();
            const cards = document.querySelectorAll('.courier-card');
            let visible = 0;

            cards.forEach((card) => {
                const match = card.textContent.toLowerCase().includes(query);
                card.style.display = match ? '' : 'none';

                if (match) {
                    visible += 1;
                }
            });

            const emptyState = document.getElementById('empty-search-state');
            if (emptyState) {
                emptyState.classList.toggle('hidden', visible > 0 || cards.length === 0);
            }
        }
    </script>
</x-app-layout>
