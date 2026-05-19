<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Waybill Excel Export') }} - {{ $courier->name }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('orders.waybill-excel.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Waybill Excel Export</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">{{ $courier->name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="rounded-md bg-white p-6 shadow-md dark:bg-gray-800">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Courier Excel Queue</h3>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('orders.waybill-excel.index') }}" class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Back
                </a>
                @can('export waybill excel')
                <button
                    type="button"
                    id="downloadExcelBtn"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-600 dark:hover:bg-emerald-700"
                    {{ $orders->total() > 0 ? '' : 'disabled' }}
                >
                    Download Excel
                </button>
                @endcan
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Pending Waybill Count</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['pending_total'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Remaining printed waybill orders not yet downloaded to Excel.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Downloaded</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['downloaded_total'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Printed waybill orders already included in at least one Excel export.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Printed Waybill Orders</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['printed_total'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">All orders with saved printed waybill IDs under this courier.</p>
            </div>
        </div>

        <div class="mb-6 rounded-lg border border-gray-200 p-4 sm:p-5 dark:border-gray-700">
            <form method="GET" action="{{ route('orders.waybill-excel.show', $courier) }}">
                <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
                    <div class="xl:col-span-6">
                        <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Order ID / Waybill / Customer / Mobile"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        >
                    </div>
                    <div class="xl:col-span-2">
                        <label for="date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="{{ request('date') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                    </div>
                    <div class="xl:col-span-2">
                        <label for="per_page" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Rows</label>
                        <select id="per_page" name="per_page" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @foreach([25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Downloaded</span>
                        <label class="flex h-[42px] items-center rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <input
                                type="checkbox"
                                name="show_downloaded"
                                value="1"
                                {{ request()->boolean('show_downloaded') ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600"
                            >
                            <span class="ms-2">Show downloaded</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Apply Filters
                        </button>
                        <a href="{{ route('orders.waybill-excel.show', $courier) }}" class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        @can('export waybill excel')
            <form action="{{ route('orders.waybill-excel.export', $courier) }}" method="POST" target="waybillExcelDownloadFrame" id="waybillExcelForm">
                @csrf
                <input type="hidden" name="search" value="{{ request('search', '') }}">
                <input type="hidden" name="date" value="{{ request('date', '') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                @if(request()->boolean('show_downloaded'))
                    <input type="hidden" name="show_downloaded" value="1">
                @endif
            </form>
        @endcan

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $orders->count() }} of {{ $orders->total() }} matching orders
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Download includes all matching rows across pages.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Order ID</th>
                            <th scope="col" class="px-4 py-3">Waybill</th>
                            <th scope="col" class="px-4 py-3">Customer</th>
                            <th scope="col" class="px-4 py-3">Mobile</th>
                            <th scope="col" class="px-4 py-3">Address</th>
                            <th scope="col" class="px-4 py-3 text-right">Net Total</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($orders as $order)
                            @php
                                $exported = (bool) $order->waybill_excel_exported_at;
                                $deliveryStatus = strtolower((string) ($order->delivery_status ?? 'waybill_printed'));
                                $deliveryLabels = [
                                    'pending' => 'Pending',
                                    'waybill_printed' => 'Waybill Printed',
                                    'picked_from_rack' => 'Picked From Rack',
                                    'packed' => 'Packed',
                                    'dispatched' => 'Dispatched',
                                    'delivered' => 'Delivered',
                                    'returned' => 'Returned',
                                    'cancel' => 'Cancelled',
                                ];
                            @endphp
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Printed {{ optional($order->waybill_printed_at)->format('d M Y') ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $order->waybill_number ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ $order->customer_name ?: ($order->customer->name ?? 'N/A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $order->customer_phone ?: ($order->customer->mobile ?? 'N/A') }}
                                </td>
                                <td class="max-w-sm break-words px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $order->customer_address ?: ($order->customer->address ?? 'N/A') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                    {{ number_format((float) $order->total_amount, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex w-fit items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $exported ? 'border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                            {{ $exported ? 'Downloaded' : 'Pending Export' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $deliveryLabels[$deliveryStatus] ?? ucwords(str_replace('_', ' ', $deliveryStatus)) }}
                                            @if($exported && $order->waybill_excel_exported_at)
                                                • {{ $order->waybill_excel_exported_at->format('d M Y, h:i A') }}
                                            @endif
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No printed waybill orders found for the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>

    @can('export waybill excel')
    <iframe name="waybillExcelDownloadFrame" class="hidden"></iframe>

    <script>
        (function () {
            const downloadButton = document.getElementById('downloadExcelBtn');
            const exportForm = document.getElementById('waybillExcelForm');

            if (!downloadButton || !exportForm) {
                return;
            }

            downloadButton.addEventListener('click', function () {
                exportForm.submit();
                window.setTimeout(function () {
                    window.location.reload();
                }, 1200);
            });
        })();
    </script>
    @endcan
</x-app-layout>
