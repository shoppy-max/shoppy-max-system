<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Packing & Dispatch') }}
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
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Packing</span>
                        </div>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stageConfig['title'] }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    @php
        $pickGrnPayloads = $orders->getCollection()
            ->filter(fn ($order) => filled($order->pick_grn_number) && $order->pick_grn_modal_payload)
            ->mapWithKeys(fn ($order) => [(string) $order->id => $order->pick_grn_modal_payload])
            ->all();
    @endphp

    <div class="rounded-md bg-white p-6 shadow-md dark:bg-gray-800" x-data="{ activePickGrn: null, pickGrnPayloads: @js($pickGrnPayloads) }">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $stageConfig['title'] }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stageConfig['description'] }}</p>
            </div>
            @canany(['view orders', 'view own orders'])
                <a href="{{ route('orders.index', ['view' => 'active']) }}" class="inline-flex w-full items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 sm:w-auto">
                    Orders
                </a>
            @endcanany
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @can('view ready to pick orders')
            <a href="{{ $stageRoutes['ready'] }}" class="rounded-lg border p-4 transition {{ $stage === 'ready' ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-900/20' : 'border-gray-200 bg-gray-50 hover:border-indigo-300 hover:bg-indigo-50 dark:border-gray-700 dark:bg-gray-900/20 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/20' }}">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Ready To Pick</p>
                <p class="mt-1 text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ number_format($stats['waybill_printed'] ?? 0) }}</p>
            </a>
            @endcan
            @canany(['view picking orders', 'scan packing'])
            <a href="{{ $stageRoutes['picking'] }}" class="rounded-lg border p-4 transition {{ $stage === 'picking' ? 'border-purple-300 bg-purple-50 dark:border-purple-700 dark:bg-purple-900/20' : 'border-gray-200 bg-gray-50 hover:border-purple-300 hover:bg-purple-50 dark:border-gray-700 dark:bg-gray-900/20 dark:hover:border-purple-700 dark:hover:bg-purple-900/20' }}">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Picking / Scanning</p>
                <p class="mt-1 text-2xl font-bold text-purple-700 dark:text-purple-300">{{ number_format($stats['picked_from_rack'] ?? 0) }}</p>
            </a>
            @endcanany
            @can('view packed orders')
            <a href="{{ $stageRoutes['packed'] }}" class="rounded-lg border p-4 transition {{ $stage === 'packed' ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-200 bg-gray-50 hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-900/20 dark:hover:border-blue-700 dark:hover:bg-blue-900/20' }}">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Packed</p>
                <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($stats['packed'] ?? 0) }}</p>
            </a>
            @endcan
            @can('view dispatched orders')
            <a href="{{ $stageRoutes['dispatched'] }}" class="rounded-lg border p-4 transition {{ $stage === 'dispatched' ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/20' : 'border-gray-200 bg-gray-50 hover:border-emerald-300 hover:bg-emerald-50 dark:border-gray-700 dark:bg-gray-900/20 dark:hover:border-emerald-700 dark:hover:bg-emerald-900/20' }}">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Dispatched</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['dispatched'] ?? 0) }}</p>
            </a>
            @endcan
        </div>

        <div class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <form method="GET" action="{{ match ($stage) { 'picking' => route('orders.packing.picking'), 'packed' => route('orders.packing.packed'), 'dispatched' => route('orders.packing.dispatched'), default => route('orders.packing.ready') } }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Order, waybill, customer, SKU, label, rack, or GRN" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                </div>
                <div class="lg:col-span-2">
                    <label for="per_page" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Rows</label>
                    <select id="per_page" name="per_page" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach([15, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2 lg:col-span-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ match ($stage) { 'picking' => route('orders.packing.picking'), 'packed' => route('orders.packing.packed'), 'dispatched' => route('orders.packing.dispatched'), default => route('orders.packing.ready') } }}" class="inline-flex flex-1 items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $orders->count() }} of {{ $orders->total() }} matching orders
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Waybill / Courier</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Scan Progress</th>
                            <th class="px-4 py-3">Pick Locations</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($orders as $order)
                            @php
                                $summary = $order->packing_summary ?? ['items' => [], 'all_scanned' => false];
                                $requiredCount = collect($summary['items'])->sum('required_count');
                                $scannedCount = collect($summary['items'])->sum('scanned_count');
                                $allocatedCount = collect($summary['items'])->sum(fn ($item) => count($item['units'] ?? []));
                                $progressPercent = $requiredCount > 0 ? min(100, (int) floor(($scannedCount / $requiredCount) * 100)) : 0;
                                $pickLocations = collect($summary['items'])
                                    ->flatMap(fn ($item) => collect($item['units'] ?? [])->map(fn ($unit) => [
                                        'location' => ($unit['store_label'] ?? 'Unassigned Store') . ' · ' . ($unit['rack_label'] ?? 'Unassigned Rack'),
                                        'source' => $unit['purchase_number'] ?? 'Legacy stock',
                                    ]))
                                    ->unique(fn ($entry) => $entry['location'] . '|' . $entry['source'])
                                    ->values();
                                $requiresCourierReceive = trim((string) ($order->payment_method ?? 'COD')) === 'COD'
                                    && blank($order->courier_payment_id)
                                    && round((float) ($order->total_amount ?? 0), 2) > round((float) ($order->paid_amount ?? 0), 2);
                            @endphp
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ optional($order->order_date)->format('d M Y') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono text-gray-900 dark:text-white">{{ $order->waybill_number ?: '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->courier?->name ?? 'Courier not assigned' }}</div>
                                    @if($order->pick_grn_number)
                                        <div class="mt-1 text-xs font-medium text-blue-700 dark:text-blue-300">Pick GRN: {{ $order->pick_grn_number }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $order->customer_name ?: ($order->customer->name ?? '-') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->customer_phone ?: ($order->customer->mobile ?? '-') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ number_format($scannedCount) }} / {{ number_format($requiredCount) }} scanned</span>
                                        <span class="{{ $allocatedCount < $requiredCount ? 'text-amber-700 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400' }}">{{ number_format($allocatedCount) }} allocated</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-2 rounded-full {{ $progressPercent >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($pickLocations->isNotEmpty())
                                        <div class="space-y-1">
                                            @foreach($pickLocations->take(3) as $location)
                                                <div class="text-xs">
                                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $location['location'] }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">· GRN: {{ $location['source'] }}</span>
                                                </div>
                                            @endforeach
                                            @if($pickLocations->count() > 3)
                                                <div class="text-xs text-gray-400">+{{ $pickLocations->count() - 3 }} more</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-amber-600 dark:text-amber-300">No allocated rack labels</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex min-w-[360px] justify-end gap-2">
                                        @canany(['view orders', 'view own orders'])
                                            <a href="{{ route('orders.show', $order) }}" class="inline-flex min-w-[84px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                View
                                            </a>
                                        @endcanany
                                        @if($order->pick_grn_number && $stage === 'picking')
                                            <button type="button" @click="activePickGrn = pickGrnPayloads[@js((string) $order->id)] || null" class="inline-flex min-w-[104px] items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/40">
                                                Pick GRN
                                            </button>
                                        @endif
                                        @if($stage === 'ready')
                                            @can('create pick grns')
                                            <form action="{{ route('orders.packing.mark-picked', $order->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex min-w-[142px] items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-xs font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                                    Create Pick GRN
                                                </button>
                                            </form>
                                            @endcan
                                        @elseif($stage === 'packed')
                                            @can('dispatch orders')
                                            <form action="{{ route('orders.packing.mark-dispatched', $order->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex min-w-[104px] items-center justify-center rounded-lg bg-cyan-600 px-4 py-2 text-xs font-medium text-white hover:bg-cyan-700 focus:ring-4 focus:ring-cyan-300">
                                                    Dispatch
                                                </button>
                                            </form>
                                            @endcan
                                        @elseif($stage === 'dispatched')
                                            @if($requiresCourierReceive)
                                                @can('view courier receive')
                                                    @if($order->courier_id)
                                                        <a href="{{ route('courier-receive.show', $order->courier_id) }}" class="inline-flex min-w-[148px] items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-xs font-medium text-white hover:bg-amber-700 focus:ring-4 focus:ring-amber-300">
                                                            Receive Payment
                                                        </a>
                                                    @else
                                                        <span class="inline-flex min-w-[148px] items-center justify-center rounded-lg bg-amber-50 px-4 py-2 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                                            Courier Missing
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="inline-flex min-w-[148px] items-center justify-center rounded-lg bg-gray-100 px-4 py-2 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                                        Courier Receive Needed
                                                    </span>
                                                @endcan
                                            @else
                                                @can('deliver orders')
                                                <form
                                                    action="{{ route('orders.packing.mark-delivered', $order->id) }}"
                                                    method="POST"
                                                    data-confirm-title="Mark order delivered?"
                                                    data-confirm-message="Mark {{ $order->order_number }} as delivered and close its allocated stock units?"
                                                    data-confirm-icon="question"
                                                    data-confirm-button-text="Mark Delivered"
                                                    data-confirm-button-color="#047857"
                                                >
                                                    @csrf
                                                    <button type="submit" class="inline-flex min-w-[132px] items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300">
                                                        Mark Delivered
                                                    </button>
                                                </form>
                                                @endcan
                                            @endif
                                        @else
                                            @can('scan packing')
                                            <a href="{{ route('orders.packing.process', $order->id) }}" class="inline-flex min-w-[96px] items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-xs font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                                Scan
                                            </a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    {{ $stageConfig['empty'] }}
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

        @if(session('pick_grn_modal'))
            @php($pickGrnModal = session('pick_grn_modal'))
            <div
                x-data="{ open: true }"
                x-show="open"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pick-grn-modal-title"
            >
                <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 id="pick-grn-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">Pick GRN Created</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Print or save this sheet before scanning from Picking.</p>
                            </div>
                            <button type="button" @click="open = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200" aria-label="Close">
                                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 12 12M13 1 1 13"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-5">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                            <p class="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300">Pick GRN No</p>
                            <p class="mt-1 font-mono text-xl font-bold text-blue-900 dark:text-blue-100">{{ $pickGrnModal['number'] ?? '-' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Order</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $pickGrnModal['order_number'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Waybill</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $pickGrnModal['waybill_number'] ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Items To Pick</p>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse(collect($pickGrnModal['items'] ?? []) as $item)
                                    <div class="px-4 py-3">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['product_name'] ?? '-' }}</p>
                                                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['sku'] ?? '-' }}</p>
                                            </div>
                                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ number_format((int) ($item['required_count'] ?? 0)) }} qty</span>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            @forelse(collect($item['units'] ?? []) as $unit)
                                                <div class="grid gap-2 rounded-md bg-gray-50 px-3 py-2 text-xs dark:bg-gray-900/40 sm:grid-cols-4">
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">Barcode</span>
                                                        <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $unit['barcode_value'] ?? '-' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">Store</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ $unit['store_label'] ?? 'Unassigned Store' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">Rack / Row</span>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ $unit['rack_label'] ?? 'Unassigned Rack' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">GRN Source</span>
                                                        <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $unit['purchase_number'] ?? 'Legacy stock' }}</span>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">No allocated unit lines found for this item.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">No pick item lines found.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                        <a href="{{ $pickGrnModal['picking_url'] ?? route('orders.packing.picking') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Go To Picking
                        </a>
                        @canany(['view pick grns', 'create pick grns', 'scan packing'])
                        <a href="{{ $pickGrnModal['print_url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Print / Save PDF
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
        @endif

        @if($stage === 'picking')
            <div
                x-show="activePickGrn"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pick-grn-details-title"
            >
                <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl dark:bg-gray-800" @click.outside="activePickGrn = null">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 id="pick-grn-details-title" class="text-lg font-semibold text-gray-900 dark:text-white">Pick GRN Details</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review the pick sheet details or print it in a new tab.</p>
                            </div>
                            <button type="button" @click="activePickGrn = null" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200" aria-label="Close">
                                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 12 12M13 1 1 13"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-5">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                            <p class="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300">Pick GRN No</p>
                            <p class="mt-1 font-mono text-xl font-bold text-blue-900 dark:text-blue-100" x-text="activePickGrn?.number || '-'"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Order</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white" x-text="activePickGrn?.order_number || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Waybill</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white" x-text="activePickGrn?.waybill_number || '-'"></p>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Items To Pick</p>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="item in (activePickGrn?.items || [])" :key="`${item.sku}-${item.product_name}`">
                                    <div class="px-4 py-3">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.product_name || '-'"></p>
                                                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400" x-text="item.sku || '-'"></p>
                                            </div>
                                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="`${Number(item.required_count || 0).toLocaleString()} qty`"></span>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            <template x-for="unit in (item.units || [])" :key="unit.id || unit.barcode_value">
                                                <div class="grid gap-2 rounded-md bg-gray-50 px-3 py-2 text-xs dark:bg-gray-900/40 sm:grid-cols-4">
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">Barcode</span>
                                                        <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="unit.barcode_value || '-'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">Store</span>
                                                        <span class="font-medium text-gray-900 dark:text-white" x-text="unit.store_label || 'Unassigned Store'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">Rack / Row</span>
                                                        <span class="font-medium text-gray-900 dark:text-white" x-text="unit.rack_label || 'Unassigned Rack'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-gray-500 dark:text-gray-400">GRN Source</span>
                                                        <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="unit.purchase_number || 'Legacy stock'"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="!(activePickGrn?.items || []).length" class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    No pick item lines found.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                        <button type="button" @click="activePickGrn = null" class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Close
                        </button>
                        @canany(['view pick grns', 'create pick grns', 'scan packing'])
                        <a :href="activePickGrn?.print_url || '#'" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Print / Save PDF
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
