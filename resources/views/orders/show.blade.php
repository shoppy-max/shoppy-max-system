<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between no-print">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Order Details') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Back
                </a>
                <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Edit
                </a>
                <a href="{{ route('orders.pdf', $order) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Download PDF
                </a>
                <a href="{{ route('orders.print', ['order' => $order, 'autoprint' => 1]) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Print
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $statusLabels = [
            'pending' => 'Pending',
            'confirm' => 'Confirm',
            'hold' => 'Hold',
            'cancel' => 'Cancel',
        ];
        $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirm' => 'bg-green-100 text-green-800',
            'hold' => 'bg-amber-100 text-amber-800',
            'cancel' => 'bg-red-100 text-red-800',
        ];
        $deliveryLabels = [
            'pending' => 'Pending',
            'waybill_printed' => 'Waybill printed',
            'picked_from_rack' => 'Picked from rack',
            'packed' => 'Packed',
            'dispatched' => 'Dispatched',
            'delivered' => 'Delivered',
            'return_requested' => 'Return requested',
            'returned' => 'Returned',
            'cancel' => 'Cancel',
        ];
        $deliveryColors = [
            'pending' => 'bg-gray-100 text-gray-800',
            'waybill_printed' => 'bg-indigo-100 text-indigo-800',
            'picked_from_rack' => 'bg-purple-100 text-purple-800',
            'packed' => 'bg-blue-100 text-blue-800',
            'dispatched' => 'bg-cyan-100 text-cyan-800',
            'delivered' => 'bg-green-100 text-green-800',
            'return_requested' => 'bg-amber-100 text-amber-800',
            'returned' => 'bg-orange-100 text-orange-800',
            'cancel' => 'bg-red-100 text-red-800',
        ];
        $orderStatus = strtolower((string) ($order->status ?? 'pending'));
        $deliveryStatus = strtolower((string) ($order->delivery_status ?? 'pending'));
        $customerName = $order->customer->name ?? $order->customer_name ?? '-';
        $customerMobile = $order->customer->mobile ?? $order->customer_phone ?? '-';
        $customerAddress = $order->customer->address ?? $order->customer_address ?? '-';
        $itemSubTotal = (float) $order->items->sum(fn ($item) => (float) ($item->subtotal ?? ($item->unit_price * $item->quantity)));
        $discountAmount = (float) ($order->discount_amount ?? 0);
        $courierCharge = (float) ($order->courier_charge ?? 0);
        $commission = (float) ($order->total_commission ?? 0);
        $onlinePayment = (string) ($order->payment_method ?? '') === 'Online Payment' ? (float) ($order->paid_amount ?? 0) : 0.0;
        $returnFeeDeduction = ((string) ($order->order_type ?? '') === 'reseller' && $deliveryStatus === 'returned')
            ? (float) ($order->reseller_return_fee_applied ?? 0)
            : 0.0;
        $balance = max((float) ($order->total_amount ?? 0) - (float) ($order->paid_amount ?? 0) - $returnFeeDeduction, 0);
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $order->order_number }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Created {{ optional($order->created_at)->format('d M Y, h:i A') ?? '-' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide {{ $statusColors[$orderStatus] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$orderStatus] ?? ucfirst($orderStatus) }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold tracking-wide {{ $deliveryColors[$deliveryStatus] ?? 'bg-gray-100 text-gray-800' }}">
                            Delivery: {{ $deliveryLabels[$deliveryStatus] ?? ucfirst($deliveryStatus) }}
                        </span>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Customer Info</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Customer Name</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $customerName }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Mobile</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $customerMobile }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Address</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100 break-words">{{ $customerAddress }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 xl:col-span-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Delivery Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Order Create Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->created_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Waybill Print Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->waybill_printed_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Pick Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->picked_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Pack Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->packed_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Dispatched Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->dispatched_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Cancel Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->cancelled_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Delivered Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->delivered_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Return Date & Time</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ optional($order->returned_at)->format('d M Y, h:i A') ?? '-' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 xl:col-span-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Order ID</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $order->order_number }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Waybill</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $order->waybill_number ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Delivery Status</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $deliveryLabels[$deliveryStatus] ?? ucfirst($deliveryStatus) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Payment Method</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $order->payment_method ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Order Create User</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $order->user->name ?? '-' }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Product Details</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Product Image</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Product Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">SKU</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Tracked Units / Source</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Variant</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($order->items as $item)
                                @php
                                    $variant = $item->variant;
                                    $variantLabel = trim((string) ($variant?->unit_value ?? '') . ' ' . (string) ($variant?->unit?->short_name ?? ''));
                                    $imagePath = $variant?->image ?? $variant?->product?->image ?? null;
                                    $imageUrl = $imagePath
                                        ? (str_starts_with((string) $imagePath, 'http://') || str_starts_with((string) $imagePath, 'https://') || str_starts_with((string) $imagePath, '/')
                                            ? $imagePath
                                            : asset('storage/' . ltrim((string) $imagePath, '/')))
                                        : null;
                                    $lineTotal = (float) ($item->subtotal ?? ((float) $item->unit_price * (int) $item->quantity));
                                @endphp
                                <tr class="bg-white dark:bg-gray-800">
                                    <td class="px-4 py-3">
                                        @if($imageUrl)
                                            <img src="{{ $imageUrl }}" alt="{{ $item->product_name }}" class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                                        @else
                                            <div class="h-12 w-12 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center text-[10px] text-gray-500 dark:text-gray-400">
                                                No Image
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->product_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->sku ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        @if($item->inventoryUnits->isNotEmpty())
                                            <div class="space-y-1">
                                                @foreach($item->inventoryUnits as $trackedUnit)
                                                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/40">
                                                        <div class="font-mono text-[11px] text-gray-800 dark:text-gray-200">{{ $trackedUnit->unit_code }}</div>
                                                        <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                                            {{ $trackedUnit->purchase?->purchase_number ? 'Source: ' . $trackedUnit->purchase->purchase_number : 'Source: Legacy stock' }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $variantLabel !== '' ? $variantLabel : '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100">{{ (int) $item->quantity }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">LKR {{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">LKR {{ number_format($lineTotal, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No products found in this order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <section class="xl:col-span-2 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Sales Note</h3>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                        {{ $order->sales_note ?: '-' }}
                    </div>
                </section>

                <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Update</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Sub Total</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">LKR {{ number_format($itemSubTotal, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Reseller Commission</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">LKR {{ number_format($commission, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Delivery Charge</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">LKR {{ number_format($courierCharge, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Discount</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">LKR {{ number_format($discountAmount, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Online Payment</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">LKR {{ number_format($onlinePayment, 2) }}</dd>
                        </div>
                        @if($returnFeeDeduction > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Return Fee Penalty</dt>
                                <dd class="font-medium text-amber-700 dark:text-amber-300">-LKR {{ number_format($returnFeeDeduction, 2) }}</dd>
                            </div>
                        @endif
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <dt class="text-base font-semibold text-gray-900 dark:text-gray-100">Balance</dt>
                            <dd class="text-base font-semibold text-gray-900 dark:text-gray-100">LKR {{ number_format($balance, 2) }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
