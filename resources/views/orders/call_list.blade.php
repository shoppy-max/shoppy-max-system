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
                <table class="w-full min-w-[1280px] text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Order #</th>
                        <th scope="col" class="px-6 py-3">Customer</th>
                        <th scope="col" class="px-6 py-3">Mobile</th>
                        <!-- Items column removed -->
                        <th scope="col" class="px-6 py-3">Total</th>
                        <th scope="col" class="px-6 py-3 min-w-[160px]">Payment Method</th>
                        <th scope="col" class="px-6 py-3 min-w-[140px]">Courier Charge</th>
                        <th scope="col" class="px-6 py-3 min-w-[120px]">Order Status</th>
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
                            }
                        }">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <button type="button" @click="viewOrder({{ json_encode($order) }})" class="hover:underline text-blue-600 font-bold focus:outline-none">
                                    {{ $order->order_number }}
                                </button>
                                <div class="text-xs text-gray-400">{{ $order->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $order->customer->name ?? $order->customer_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white select-all">{{ $order->customer->mobile ?? $order->customer_phone }}</span>
                                    <button class="ml-2 text-gray-400 hover:text-gray-600" title="Copy" onclick="navigator.clipboard.writeText('{{ $order->customer->mobile ?? $order->customer_phone }}')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                            </td>
                             <!-- Items cell removed -->
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                {{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $paymentMethod = (string) ($order->payment_method ?? '');
                                    $paymentMethodColors = [
                                        'COD' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'Online Payment' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    ];
                                @endphp
                                <span class="inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-medium {{ $paymentMethodColors[$paymentMethod] ?? 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600' }}">
                                    {{ $paymentMethod ?: 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                LKR {{ number_format((float) ($order->courier_charge ?? 0), 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs select-none">
                                    {{ ucfirst($order->status) }}
                                </span>
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
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
                        <p class="text-sm text-gray-500" x-text="new Date(selectedOrder?.created_at).toLocaleDateString()"></p>
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
                                <span class="text-gray-500">Payment:</span>
                                <span class="font-medium dark:text-white" x-text="selectedOrder?.payment_method"></span>
                             </div>
                              <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Courier:</span>
                                <span class="font-medium dark:text-white" x-text="selectedOrder?.courier_id ? 'Assigned' : 'Not Assigned'"></span>
                             </div>
                             <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Status:</span>
                                <span class="font-medium capitalize" :class="{
                                    'text-yellow-600': selectedOrder?.status === 'pending',
                                    'text-green-600': selectedOrder?.status === 'confirm',
                                    'text-red-600': selectedOrder?.status === 'cancelled',
                                    'text-orange-600': selectedOrder?.status === 'hold'
                                }" x-text="selectedOrder?.status"></span>
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
                             <span class="font-medium dark:text-white" x-text="Number(selectedOrder?.items?.reduce((sum, item) => sum + Number(item.subtotal), 0) || 0).toFixed(2)"></span>
                        </div>
                        <template x-if="Number(selectedOrder?.courier_charge) > 0">
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Courier Charge</span>
                                <span class="font-medium dark:text-white" x-text="Number(selectedOrder?.courier_charge).toFixed(2)"></span>
                            </div>
                        </template>
                         <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white mt-2">
                            <span>Grand Total</span>
                            <span x-text="Number(selectedOrder?.total_amount).toFixed(2)"></span>
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
                }
            }
        }
    </script>
</x-app-layout>
