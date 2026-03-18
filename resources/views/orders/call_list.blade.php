<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Call List') }}
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
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('orders.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Orders</a>
                        </div>
                    </li>
                    <li aria-current="page">
                         <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Call List</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800" x-data="callListManager()">
        
        <!-- Filter bar -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <form method="GET" action="{{ route('orders.call-list') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Search -->
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Search Order #, Phone...">
                </div>

                <!-- Call Status Filter -->
                <div>
                        <select name="call_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">All Call Status</option>
                        <option value="pending" {{ request('call_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirm" {{ request('call_status') == 'confirm' ? 'selected' : '' }}>Confirm</option>
                        <option value="hold" {{ request('call_status') == 'hold' ? 'selected' : '' }}>Hold</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="From Date">
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        Filter
                    </button>
                    <a href="{{ route('orders.call-list') }}" class="flex-1 flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="relative overflow-x-auto sm:rounded-lg">
                <table class="w-full min-w-[1320px] text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Order Date</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Order ID</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">User</th>
                        <th scope="col" class="px-6 py-3">Customer</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Mobile</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Total Amount</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Paid Amount</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">Balance</th>
                        <th scope="col" class="px-6 py-3 min-w-[170px]">Call Status</th>
                        <th scope="col" class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors" x-data="{ 
                            updating: false, 
                            currentStatus: '{{ $order->call_status }}',
                            updateStatus(newStatus) {
                                if (this.currentStatus === newStatus) return;
                                this.updating = true;
                                fetch('{{ route('orders.status.update', $order->id) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        // 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content 
                                        // Using blade output for safety if meta missing
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ call_status: newStatus })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    this.updating = false;
                                    if(data.success) {
                                        this.currentStatus = data.call_status;
                                        // Optional: Show toast
                                    } else {
                                        alert('Failed to update status');
                                    }
                                })
                                .catch(() => {
                                    this.updating = false;
                                    alert('Error updating status');
                                });
                            },
                            async cancelOrder() {
                                const orderNumber = {{ \Illuminate\Support\Js::from($order->order_number) }};
                                const confirmText = `Cancel ${orderNumber}? This will cancel the order and set call and delivery statuses to Cancel.`;

                                if (typeof Swal !== 'undefined') {
                                    const result = await Swal.fire({
                                        title: 'Cancel Order',
                                        text: confirmText,
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#dc2626',
                                        cancelButtonColor: '#6b7280',
                                        confirmButtonText: 'Yes, Cancel Order',
                                        cancelButtonText: 'Keep Order',
                                    });

                                    if (!result.isConfirmed) {
                                        return;
                                    }
                                } else if (!confirm(confirmText)) {
                                    return;
                                }

                                this.updating = true;
                                fetch('{{ route('orders.status.update', $order->id) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ status: 'cancel' })
                                })
                                .then(async (response) => {
                                    const data = await response.json().catch(() => ({}));
                                    this.updating = false;

                                    if (!response.ok || !data.success) {
                                        const message = data?.message || 'Failed to cancel order.';
                                        if (typeof Swal !== 'undefined') {
                                            await Swal.fire({
                                                icon: 'error',
                                                title: 'Cancel Failed',
                                                text: message,
                                            });
                                        } else {
                                            alert(message);
                                        }
                                        return;
                                    }

                                    if (typeof Swal !== 'undefined') {
                                        await Swal.fire({
                                            icon: 'success',
                                            title: 'Order Cancelled',
                                            text: `${orderNumber} was cancelled successfully.`,
                                            timer: 1200,
                                            showConfirmButton: false,
                                        });
                                    }
                                    window.location.reload();
                                })
                                .catch(() => {
                                    this.updating = false;
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Cancel Failed',
                                            text: 'An unexpected error occurred while cancelling the order.',
                                        });
                                    } else {
                                        alert('An unexpected error occurred while cancelling the order.');
                                    }
                                });
                            }
                        }">
                            @php
                                $mobile = $order->customer->mobile ?? $order->customer_phone ?? '-';
                                $paidAmount = (float) ($order->paid_amount ?? 0);
                                $returnFeeDeduction = ((string) ($order->order_type ?? '') === 'reseller'
                                    && strtolower((string) ($order->delivery_status ?? '')) === 'returned')
                                    ? (float) ($order->reseller_return_fee_applied ?? 0)
                                    : 0;
                                $balanceAmount = max(((float) $order->total_amount) - $paidAmount - $returnFeeDeduction, 0);
                                $orderDate = optional($order->order_date)->format('d M Y') ?? optional($order->created_at)->format('d M Y');
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                {{ $orderDate ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <button type="button" @click="viewOrder({{ json_encode($order) }})" class="hover:underline text-blue-600 font-bold focus:outline-none">
                                    {{ $order->order_number }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                {{ $order->user->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $order->customer->name ?? $order->customer_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white select-all">{{ $mobile }}</span>
                                    <button class="ml-2 text-gray-400 hover:text-gray-600" title="Copy" onclick="navigator.clipboard.writeText('{{ $mobile }}')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                LKR {{ number_format((float) $order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                LKR {{ number_format($paidAmount, 2) }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                LKR {{ number_format($balanceAmount, 2) }}
                            </td>
                            <td class="px-6 py-4 min-w-[170px]">
                                <div class="relative">
                                    <select @change="updateStatus($event.target.value)" 
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm font-medium rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full min-w-[150px] px-3 py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                            :class="{'opacity-50 pointer-events-none': updating, 'bg-green-50 text-green-800 border-green-300': currentStatus === 'confirm', 'bg-orange-50 text-orange-800 border-orange-300': currentStatus === 'hold'}"
                                    >
                                        <option value="pending" :selected="currentStatus === 'pending'">Pending</option>
                                        <option value="confirm" :selected="currentStatus === 'confirm'">Confirm</option>
                                        <option value="hold" :selected="currentStatus === 'hold'">Hold</option>
                                    </select>
                                    <div x-show="updating" class="absolute inset-y-0 right-0 flex items-center pr-8 pointer-events-none">
                                        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-3">
                                    <button @click="viewOrder({{ json_encode($order) }})" class="text-green-600 hover:text-green-800" title="Quick View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <a href="{{ route('orders.edit', $order) }}" class="text-blue-600 hover:text-blue-800" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <button type="button" @click="cancelOrder()" :disabled="updating" class="text-red-600 hover:text-red-800 disabled:opacity-40 disabled:cursor-not-allowed" title="Cancel Order">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                No orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $orders->withQueryString()->links() }}
        </div>

        <!-- Order Summary Modal -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-transition.opacity>
            <div @click.away="showModal = false" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl mx-4 overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Modal Header -->
                <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                    <div>
                         <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Order: <span x-text="selectedOrder?.order_number"></span>
                        </h3>
                        <p class="text-sm text-gray-500" x-text="formatOrderDate(selectedOrder?.order_date || selectedOrder?.created_at)"></p>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto"  x-show="selectedOrder">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Customer -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Customer</h4>
                            <p class="text-gray-900 dark:text-white font-medium" x-text="selectedOrder?.customer?.name ?? selectedOrder?.customer_name"></p>
                             <p class="text-gray-600 dark:text-gray-400 text-sm" x-text="selectedOrder?.customer?.mobile ?? selectedOrder?.customer_phone"></p>
                             <p class="text-gray-600 dark:text-gray-400 text-sm mt-1" x-text="selectedOrder?.customer_address"></p>
                            <template x-if="selectedOrder?.customer_city">
                                <p class="text-gray-600 dark:text-gray-400 text-sm" x-text="`${selectedOrder.customer_city}, ${selectedOrder.customer_district || ''}`"></p>
                            </template>
                        </div>
                        
                         <!-- Info -->
                        <div>
                             <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Details</h4>
                             <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">User:</span>
                                <span class="font-medium dark:text-white" x-text="selectedOrder?.user?.name || '-'"></span>
                             </div>
                             <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Payment:</span>
                                <span class="font-medium dark:text-white" x-text="selectedOrder?.payment_method || '-'"></span>
                             </div>
                              <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Courier:</span>
                                <span class="font-medium dark:text-white" x-text="selectedOrder?.courier?.name || (selectedOrder?.courier_id ? 'Assigned' : 'Not Assigned')"></span>
                             </div>
                             <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Delivery Status:</span>
                                <span class="font-medium dark:text-white" x-text="formatStatus(selectedOrder?.delivery_status)"></span>
                             </div>
                             <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Call Status:</span>
                                <span class="font-medium dark:text-white" x-text="formatStatus(selectedOrder?.call_status)"></span>
                             </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                     <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Order Items</h4>
                    <div class="relative overflow-x-auto border rounded-lg dark:border-gray-700 mb-6">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2">Item</th>
                                    <th class="px-4 py-2 text-center">Qty</th>
                                    <th class="px-4 py-2 text-right">Price</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in selectedOrder?.items" :key="item.id">
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                            <div x-text="item.product_name"></div>
                                            <div class="text-xs text-gray-500" x-text="item.sku"></div>
                                            <template x-if="item.inventory_units && item.inventory_units.length">
                                                <div class="mt-2" x-data="{ open: false }">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300" x-text="`${item.inventory_units.length} labels`"></span>
                                                        <span class="font-mono text-[11px] text-gray-600 dark:text-gray-300" x-text="unitRange(item.inventory_units)"></span>
                                                        <button type="button" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" @click="open = !open" x-text="open ? 'Hide' : 'View All'"></button>
                                                    </div>
                                                    <div x-show="open" x-transition.opacity class="mt-3 space-y-2">
                                                        <template x-for="trackedUnit in item.inventory_units" :key="trackedUnit.id">
                                                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                                                                <div class="font-mono text-[11px] text-gray-800 dark:text-gray-200" x-text="trackedUnit.unit_code"></div>
                                                                <div class="text-[10px] text-gray-500 dark:text-gray-400" x-text="trackedUnit.purchase?.purchase_number ? `Source: ${trackedUnit.purchase.purchase_number}` : 'Source: Legacy stock'"></div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2 text-center" x-text="item.quantity"></td>
                                        <td class="px-4 py-2 text-right" x-text="Number(item.unit_price).toFixed(2)"></td>
                                        <td class="px-4 py-2 text-right font-medium" x-text="Number(item.subtotal).toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                     <div class="flex flex-col gap-2 border-t pt-4 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                             <span class="font-medium dark:text-white" x-text="formatMoney(selectedOrder?.items?.reduce((sum, item) => sum + Number(item.subtotal), 0) || 0)"></span>
                        </div>
                        <template x-if="Number(selectedOrder?.discount_amount || 0) > 0">
                             <div class="flex justify-between text-sm text-red-600 dark:text-red-400">
                                <span>Discount</span>
                                <span class="font-medium" x-text="`- ${formatMoney(selectedOrder?.discount_amount)}`"></span>
                            </div>
                        </template>
                        <template x-if="Number(selectedOrder?.courier_charge) > 0">
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Courier Charge</span>
                                <span class="font-medium dark:text-white" x-text="formatMoney(selectedOrder?.courier_charge)"></span>
                            </div>
                        </template>
                        <template x-if="isReturnedResellerOrder(selectedOrder) && Number(selectedOrder?.reseller_return_fee_applied || 0) > 0">
                             <div class="flex justify-between text-sm text-amber-600 dark:text-amber-400">
                                <span>Return Fee Penalty</span>
                                <span class="font-medium" x-text="`- ${formatMoney(selectedOrder?.reseller_return_fee_applied)}`"></span>
                            </div>
                        </template>
                         <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white mt-2">
                            <span>Grand Total</span>
                            <span x-text="formatMoney(selectedOrder?.total_amount)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Paid Amount</span>
                            <span class="font-medium dark:text-white" x-text="formatMoney(selectedOrder?.paid_amount)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500" x-text="selectedOrder?.payment_method === 'COD' ? 'Remaining (COD Collect)' : 'Balance'"></span>
                            <span class="font-medium dark:text-white" x-text="formatMoney(getOrderBalance(selectedOrder))"></span>
                        </div>
                     </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 flex justify-end gap-2">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Close
                    </button>
                    <a :href="`/orders/${selectedOrder?.id}/edit`" class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Edit Order
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
        function callListManager() {
            return {
                showModal: false,
                selectedOrder: null,
                viewOrder(order) {
                    this.selectedOrder = order;
                    this.showModal = true;
                },
                formatOrderDate(value) {
                    if (!value) {
                        return '-';
                    }

                    const date = new Date(value);
                    if (Number.isNaN(date.getTime())) {
                        return value;
                    }

                    return date.toLocaleDateString(undefined, {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                    });
                },
                formatStatus(value) {
                    if (!value) {
                        return '-';
                    }

                    return String(value)
                        .replaceAll('_', ' ')
                        .replace(/\b\w/g, (char) => char.toUpperCase());
                },
                formatMoney(value) {
                    const amount = Number(value || 0);
                    return `LKR ${Number.isFinite(amount) ? amount.toFixed(2) : '0.00'}`;
                },
                getOrderBalance(order) {
                    const total = Number(order?.total_amount || 0);
                    const paid = Number(order?.paid_amount || 0);
                    const returnFee = this.isReturnedResellerOrder(order)
                        ? Number(order?.reseller_return_fee_applied || 0)
                        : 0;

                    return Math.max(total - paid - returnFee, 0);
                },
                isReturnedResellerOrder(order) {
                    return String(order?.order_type || '') === 'reseller'
                        && String(order?.delivery_status || '').toLowerCase() === 'returned';
                },
                unitRange(units) {
                    const list = Array.isArray(units) ? units : [];
                    if (!list.length) {
                        return '-';
                    }

                    const firstCode = list[0]?.unit_code || '-';
                    const lastCode = list[list.length - 1]?.unit_code || firstCode;

                    return firstCode === lastCode ? firstCode : `${firstCode} to ${lastCode}`;
                }
            }
        }
    </script>
</x-app-layout>
