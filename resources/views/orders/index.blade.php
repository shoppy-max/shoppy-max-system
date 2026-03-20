<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Order Management') }}
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
                    <li aria-current="page">
                         <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Orders</span>
                        </div>
                    </li>
                </ol>
            </nav>
            
        </div>
    </x-slot>

    <div
        class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800"
        x-data="orderManager({
            visibleOrderIds: @js($orders->pluck('id')->values()->all()),
            waybillEligibleOrderIds: @js($orders->filter(fn ($order) => filled($order->waybill_number))->pluck('id')->values()->all()),
            bulkPdfUrl: @js(route('orders.bulk-pdf')),
            bulkReprintWaybillUrl: @js(route('orders.waybill.reprint-bulk')),
            csrf: @js(csrf_token()),
            reprintWaybillUrlTemplate: @js(route('orders.waybill.reprint', ['order' => '__ORDER__'])),
        })"
        @open-waybill-reprint.window="openReprintWaybillModal($event.detail.orderId, $event.detail.orderNumber)"
    >
        
        <!-- Filter bar -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="inline-flex w-full rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-600 dark:bg-gray-700 lg:w-auto">
                    <a href="{{ route('orders.index', array_merge(request()->except(['page', 'view']), ['view' => 'active'])) }}"
                       class="inline-flex flex-1 items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors lg:flex-none {{ $viewMode === 'active' ? 'bg-white text-blue-700 shadow-sm dark:bg-gray-800 dark:text-blue-300' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}">
                        Orders
                    </a>
                    <a href="{{ route('orders.index', array_merge(request()->except(['page', 'view']), ['view' => 'cancelled'])) }}"
                       class="inline-flex flex-1 items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors lg:flex-none {{ $viewMode === 'cancelled' ? 'bg-red-50 text-red-700 shadow-sm dark:bg-red-900/30 dark:text-red-300' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}">
                        Cancelled Orders
                    </a>
                </div>

                <a href="{{ route('orders.export', array_merge(request()->except('page'), ['view' => $viewMode])) }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 focus:outline-none dark:focus:ring-emerald-800 shadow-md">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12 4-4m-4 4-4-4M4 20h16"></path></svg>
                    Download Excel
                </a>
            </div>

             <form method="GET" action="{{ route('orders.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                        
                <!-- Search -->
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Search order #/ID, customer, reseller, user, mobile, waybill...">
                </div>

                <!-- Call Status -->
                <div>
                        <select name="call_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">All Call Status</option>
                        <option value="pending" {{ request('call_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirm" {{ request('call_status') == 'confirm' ? 'selected' : '' }}>Confirm</option>
                        <option value="hold" {{ request('call_status') == 'hold' ? 'selected' : '' }}>Hold</option>
                        @if($viewMode === 'cancelled')
                            <option value="cancel" {{ request('call_status') == 'cancel' ? 'selected' : '' }}>Cancel</option>
                        @endif
                    </select>
                </div>

                    <!-- Courier -->
                <div>
                        <select name="courier_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">All Couriers</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}" {{ request('courier_id') == $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Delivery Status -->
                <div>
                    <select name="delivery_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">All Delivery Status</option>
                        <option value="pending" {{ request('delivery_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="waybill_printed" {{ request('delivery_status') == 'waybill_printed' ? 'selected' : '' }}>Waybill Printed</option>
                        <option value="picked_from_rack" {{ request('delivery_status') == 'picked_from_rack' ? 'selected' : '' }}>Picked From Rack</option>
                        <option value="packed" {{ request('delivery_status') == 'packed' ? 'selected' : '' }}>Packed</option>
                        <option value="dispatched" {{ request('delivery_status') == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                        <option value="delivered" {{ request('delivery_status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="returned" {{ request('delivery_status') == 'returned' ? 'selected' : '' }}>Returned</option>
                        @if($viewMode === 'cancelled')
                            <option value="cancel" {{ request('delivery_status') == 'cancel' ? 'selected' : '' }}>Cancel</option>
                        @endif
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="From Date">
                </div>

                <!-- Date To -->
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="To Date">
                </div>
                
                    <!-- Payment Method -->
                <div>
                        <select name="payment_method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">All Payment Methods</option>
                        <option value="COD" {{ request('payment_method') == 'COD' ? 'selected' : '' }}>COD</option>
                        <option value="Cash Deposit" {{ request('payment_method') == 'Cash Deposit' ? 'selected' : '' }}>Cash Deposit</option>
                        <option value="Online Payment" {{ request('payment_method') == 'Online Payment' ? 'selected' : '' }}>Online Payment</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        Filter
                    </button>
                    <a href="{{ route('orders.index', ['view' => $viewMode]) }}" class="flex-1 flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="relative overflow-x-auto sm:rounded-lg">
             <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-center">
                            <input
                                type="checkbox"
                                @change="toggleAllVisible($event.target.checked)"
                                :checked="isAllVisibleSelected()"
                                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                                title="Select all visible orders"
                            >
                        </th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Order Date</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Order ID</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Waybill</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Call Status</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Delivery Status</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Payment Status</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Payment Method</th>
                        <th scope="col" class="px-6 py-3">Customer</th>
                        <th scope="col" class="px-6 py-3">Mobile</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Total</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Paid</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Balance</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Reseller Commission</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Courier</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">User</th>
                        <th scope="col" class="px-6 py-3 text-center whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-4 py-4 text-center">
                                <input
                                    id="order-checkbox-{{ $order->id }}"
                                    type="checkbox"
                                    value="{{ $order->id }}"
                                    x-model="selectedOrders"
                                    class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ optional($order->order_date)->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <div>{{ $order->order_number }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">#{{ $order->id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $order->waybill_number ?: '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $callStatus = strtolower((string) $order->call_status);
                                    $callColors = [
                                        'pending' => 'text-gray-700 bg-gray-100 border-gray-300',
                                        'confirm' => 'text-blue-700 bg-blue-100 border-blue-300',
                                        'hold' => 'text-orange-700 bg-orange-100 border-orange-300',
                                        'cancel' => 'text-red-700 bg-red-100 border-red-300',
                                    ];
                                @endphp
                                <span class="{{ $callColors[$callStatus] ?? 'text-gray-700 bg-gray-100 border-gray-300' }} border px-2.5 py-0.5 rounded text-xs font-medium capitalize">
                                    {{ $callStatus ? ucfirst($callStatus) : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $deliveryStatus = strtolower((string) ($order->delivery_status ?? 'pending'));
                                    $deliveryStatusColors = [
                                        'pending' => 'text-gray-700 bg-gray-100 border-gray-300',
                                        'waybill_printed' => 'text-indigo-700 bg-indigo-100 border-indigo-300',
                                        'picked_from_rack' => 'text-purple-700 bg-purple-100 border-purple-300',
                                        'packed' => 'text-blue-700 bg-blue-100 border-blue-300',
                                        'dispatched' => 'text-cyan-700 bg-cyan-100 border-cyan-300',
                                        'delivered' => 'text-green-700 bg-green-100 border-green-300',
                                        'returned' => 'text-orange-700 bg-orange-100 border-orange-300',
                                        'cancel' => 'text-red-700 bg-red-100 border-red-300',
                                    ];
                                    $deliveryStatusLabels = [
                                        'pending' => 'Pending',
                                        'waybill_printed' => 'Waybill printed',
                                        'picked_from_rack' => 'Picked from rack',
                                        'packed' => 'Packed',
                                        'dispatched' => 'Dispatched',
                                        'delivered' => 'Delivered',
                                        'returned' => 'Returned',
                                        'cancel' => 'Cancel',
                                    ];
                                @endphp
                                <span class="{{ $deliveryStatusColors[$deliveryStatus] ?? 'text-gray-700 bg-gray-100 border-gray-300' }} border px-2.5 py-0.5 rounded text-xs font-medium whitespace-nowrap">
                                    {{ $deliveryStatusLabels[$deliveryStatus] ?? 'Pending' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $paymentStatus = strtolower((string) $order->payment_status);
                                    $paymentStatusColors = [
                                        'paid' => 'text-green-700 bg-green-100 border-green-300',
                                        'pending' => 'text-amber-700 bg-amber-100 border-amber-300',
                                        'failed' => 'text-red-700 bg-red-100 border-red-300',
                                    ];
                                @endphp
                                <span class="{{ $paymentStatusColors[$paymentStatus] ?? 'text-gray-700 bg-gray-100 border-gray-300' }} border px-2.5 py-0.5 rounded text-xs font-medium capitalize whitespace-nowrap">
                                    {{ $paymentStatus ? ucfirst($paymentStatus) : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $paymentMethod = (string) ($order->payment_method ?? '');
                                    $paymentMethodColors = [
                                        'COD' => 'bg-blue-100 text-blue-800 border-blue-300',
                                        'Cash Deposit' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'Online Payment' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    ];
                                @endphp
                                <span class="inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-medium {{ $paymentMethodColors[$paymentMethod] ?? 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600' }}">
                                    {{ $paymentMethod ?: 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $order->customer->name ?? $order->customer_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>{{ $order->customer->mobile ?? $order->customer_phone ?? '-' }}</div>
                                @if(($order->customer->landline ?? null) && ($order->customer->landline ?? null) !== ($order->customer->mobile ?? $order->customer_phone ?? null))
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Alt: {{ $order->customer->landline }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold whitespace-nowrap text-gray-900 dark:text-white">
                                LKR {{ number_format((float) $order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                LKR {{ number_format((float) ($order->paid_amount ?? 0), 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $returnFeeDeduction = ((string) ($order->order_type ?? '') === 'reseller'
                                        && strtolower((string) ($order->delivery_status ?? '')) === 'returned')
                                        ? (float) ($order->reseller_return_fee_applied ?? 0)
                                        : 0;
                                    $balance = max(((float) $order->total_amount) - ((float) ($order->paid_amount ?? 0)) - $returnFeeDeduction, 0);
                                @endphp
                                <div>LKR {{ number_format($balance, 2) }}</div>
                                @if($returnFeeDeduction > 0)
                                    <div class="text-xs text-amber-600 dark:text-amber-400">Return Fee: -LKR {{ number_format($returnFeeDeduction, 2) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                LKR {{ number_format((float) ($order->total_commission ?? 0), 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($order->courier)
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $order->courier->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Charge: LKR {{ number_format((float) ($order->courier_charge ?? 0), 2) }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $order->user->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $manualEditLocked = (bool) ($order->manual_edit_locked ?? false);
                                    $canPaymentEdit = (bool) ($order->can_payment_edit ?? false);
                                @endphp
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('orders.pdf', $order) }}" target="_blank" class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg dark:text-indigo-400 dark:hover:bg-gray-700 transition-colors" title="Download PDF">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </a>
                                    @if(filled($order->waybill_number))
                                        <button
                                            type="button"
                                            @click="openReprintWaybillModal({{ $order->id }}, @js($order->order_number))"
                                            class="p-2 text-amber-600 hover:bg-amber-100 rounded-lg dark:text-amber-400 dark:hover:bg-gray-700 transition-colors"
                                            title="Reprint Waybill"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 13H5a2 2 0 00-2 2v3h4m13-5h1a2 2 0 012 2v3h-4m-9 0h8a1 1 0 001-1v-5H9v5a1 1 0 001 1zm0 0v2m8-2v2"></path>
                                            </svg>
                                        </button>
                                    @endif
                                    <a href="{{ route('orders.show', $order) }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg dark:text-gray-400 dark:hover:bg-gray-700 transition-colors" title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    @if(!$manualEditLocked || $canPaymentEdit)
                                        <a href="{{ route('orders.edit', $order) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg dark:text-blue-400 dark:hover:bg-gray-700 transition-colors" title="{{ $manualEditLocked ? 'Update Payment' : 'Edit' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                        @if(!$manualEditLocked)
                                            <form action="{{ route('orders.destroy', $order) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this order? This will restore stock.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg dark:text-red-400 dark:hover:bg-gray-700 transition-colors" title="Delete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="p-2 text-gray-400 dark:text-gray-500" title="Manual edit, payment update, and delete are locked for this order">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3Zm0 0v2m-6 6h12a2 2 0 002-2v-5a2 2 0 00-2-2H6a2 2 0 00-2 2v5a2 2 0 002 2Z"></path></svg>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="17" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    <p class="text-lg font-medium">No orders found</p>
                                    <p class="text-sm">Adjust the filters or search to find matching orders.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-4">
            {{ $orders->links() }}
        </div>

        <div
            x-cloak
            x-show="selectedOrders.length > 0"
            class="fixed bottom-5 left-4 right-4 z-40 sm:left-auto sm:right-6 sm:max-w-xl"
        >
            <div class="rounded-xl border border-blue-200 bg-white/95 dark:bg-gray-800/95 dark:border-blue-900 shadow-xl px-4 py-3 backdrop-blur">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-200">
                        <span class="font-semibold" x-text="selectedOrders.length"></span>
                        orders selected
                        <template x-if="selectedReprintableWaybillCount() > 0">
                            <span class="text-gray-500 dark:text-gray-400">
                                • <span x-text="selectedReprintableWaybillCount()"></span> with saved waybills
                            </span>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="clearSelection()"
                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                        >
                            Clear
                        </button>
                        <button
                            type="button"
                            @click="downloadSelectedPdfs()"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800"
                        >
                            Download PDFs
                        </button>
                        <button
                            type="button"
                            @click="openBulkReprintWaybillModal()"
                            x-bind:disabled="selectedReprintableWaybillCount() === 0"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:ring-4 focus:ring-amber-300 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-amber-600 dark:hover:bg-amber-700 dark:focus:ring-amber-800"
                        >
                            Reprint Waybills
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @include('orders.partials.reprint-waybill-modal')
        @include('orders.partials.bulk-reprint-waybill-modal')
    </div>
    
    <script>
        function orderManager(config = {}) {
            return {
                selectedOrders: [],
                visibleOrderIds: (config.visibleOrderIds || []).map(id => String(id)),
                waybillEligibleOrderIds: (config.waybillEligibleOrderIds || []).map(id => String(id)),
                bulkPdfUrl: config.bulkPdfUrl || '',
                bulkReprintWaybillUrl: config.bulkReprintWaybillUrl || '',
                csrf: config.csrf || '',
                reprintWaybillModalOpen: false,
                bulkReprintWaybillModalOpen: false,
                reprintWaybillOrderId: null,
                reprintWaybillOrderNumber: '',
                reprintWaybillUrlTemplate: config.reprintWaybillUrlTemplate || '',
                isAllVisibleSelected() {
                    if (this.visibleOrderIds.length === 0) {
                        return false;
                    }

                    const selected = new Set(this.selectedOrders.map(id => String(id)));
                    return this.visibleOrderIds.every(id => selected.has(String(id)));
                },
                toggleAllVisible(checked) {
                    const selected = new Set(this.selectedOrders.map(id => String(id)));

                    if (checked) {
                        this.visibleOrderIds.forEach(id => selected.add(String(id)));
                    } else {
                        this.visibleOrderIds.forEach(id => selected.delete(String(id)));
                    }

                    this.selectedOrders = Array.from(selected);
                },
                clearSelection() {
                    this.selectedOrders = [];
                },
                selectedReprintableWaybillIds() {
                    const waybillEligible = new Set(this.waybillEligibleOrderIds.map(id => String(id)));
                    return this.selectedOrders
                        .map(id => String(id))
                        .filter(id => waybillEligible.has(id));
                },
                selectedReprintableWaybillCount() {
                    return this.selectedReprintableWaybillIds().length;
                },
                openReprintWaybillModal(orderId, orderNumber) {
                    this.reprintWaybillOrderId = String(orderId);
                    this.reprintWaybillOrderNumber = orderNumber || '';
                    this.reprintWaybillModalOpen = true;
                },
                closeReprintWaybillModal() {
                    this.reprintWaybillModalOpen = false;
                },
                reprintWaybillUrl(paperSize) {
                    if (!this.reprintWaybillUrlTemplate || !this.reprintWaybillOrderId || !paperSize) {
                        return '#';
                    }

                    const url = new URL(
                        this.reprintWaybillUrlTemplate.replace('__ORDER__', this.reprintWaybillOrderId),
                        window.location.origin
                    );
                    url.searchParams.set('paper_size', paperSize);
                    return url.toString();
                },
                openBulkReprintWaybillModal() {
                    if (this.selectedReprintableWaybillCount() === 0) {
                        return;
                    }

                    this.bulkReprintWaybillModalOpen = true;
                },
                closeBulkReprintWaybillModal() {
                    this.bulkReprintWaybillModalOpen = false;
                },
                submitBulkWaybillReprint(paperSize) {
                    const reprintableIds = this.selectedReprintableWaybillIds();

                    if (reprintableIds.length === 0 || !this.bulkReprintWaybillUrl || !paperSize) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.bulkReprintWaybillUrl;
                    form.target = 'waybillDownloadFrame';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = this.csrf;
                    form.appendChild(csrfInput);

                    const paperInput = document.createElement('input');
                    paperInput.type = 'hidden';
                    paperInput.name = 'paper_size';
                    paperInput.value = paperSize;
                    form.appendChild(paperInput);

                    reprintableIds.forEach((orderId) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'order_ids[]';
                        input.value = orderId;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                    form.remove();
                    this.closeBulkReprintWaybillModal();
                },
                downloadSelectedPdfs() {
                    if (this.selectedOrders.length === 0 || !this.bulkPdfUrl) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.bulkPdfUrl;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = this.csrf;
                    form.appendChild(csrfInput);

                    this.selectedOrders.forEach((orderId) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'order_ids[]';
                        input.value = String(orderId);
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                    form.remove();
                },
            }
        }
    </script>
</x-app-layout>
