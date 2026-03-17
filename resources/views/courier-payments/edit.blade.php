<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Edit Courier Payment') }}
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
                            <a href="{{ route('courier-payments.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Courier Payments</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('courier-payments.update', $courierPayment) }}" id="courier-payment-edit-form">
        @csrf
        @method('PUT')
        <div class="mx-auto max-w-6xl space-y-6 p-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 border-b pb-2 text-lg font-medium text-gray-900 dark:border-gray-700 dark:text-white">Payment Details</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-4" x-data="{
                        courierSearch: @js(old('courier_id') ? optional($couriers->firstWhere('id', (int) old('courier_id')))->name : $courierPayment->courier->name),
                        courierOpen: false,
                        selectedCourier: @js(['id' => old('courier_id', $courierPayment->courier->id), 'name' => old('courier_id') ? optional($couriers->firstWhere('id', (int) old('courier_id')))->name : $courierPayment->courier->name]),
                        couriers: @js($couriers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()),
                        get filteredCouriers() {
                            if (!this.courierSearch) return this.couriers;
                            return this.couriers.filter((courier) => courier.name.toLowerCase().includes(this.courierSearch.toLowerCase()));
                        },
                        selectCourier(courier) {
                            this.selectedCourier = courier;
                            this.courierSearch = courier.name;
                            this.courierOpen = false;
                        }
                    }">
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Courier <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text"
                                       x-model="courierSearch"
                                       @input="courierOpen = true"
                                       @focus="courierOpen = true"
                                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="Search courier..."
                                       autocomplete="off"
                                       required>
                            </div>
                            <div x-show="courierOpen"
                                 x-transition
                                 @click.outside="courierOpen = false"
                                 class="absolute z-50 mt-2 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-2xl dark:border-gray-600 dark:bg-gray-800">
                                <ul x-show="filteredCouriers.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="courier in filteredCouriers" :key="courier.id">
                                        <li @click="selectCourier(courier)" class="cursor-pointer px-4 py-3 transition-colors hover:bg-blue-50 dark:hover:bg-gray-700">
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="courier.name"></span>
                                        </li>
                                    </template>
                                </ul>
                                <div x-show="filteredCouriers.length === 0" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No couriers found
                                </div>
                            </div>
                            <input type="hidden" name="courier_id" id="selected_courier_id" x-model="selectedCourier?.id">
                        </div>
                        @error('courier_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Payment Date</label>
                        <input type="date" value="{{ $courierPayment->payment_date->format('Y-m-d') }}" disabled class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Payment date is locked after the courier payment is created.</p>
                    </div>

                    <div class="xl:col-span-3">
                        <label for="amount" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Received Amount</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" value="{{ old('amount', number_format((float) $courierPayment->amount, 2, '.', '')) }}" readonly class="block w-full cursor-not-allowed rounded-lg border border-emerald-200 bg-emerald-50 p-2.5 text-sm font-semibold text-emerald-900 dark:border-emerald-700/60 dark:bg-emerald-900/20 dark:text-emerald-100">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">This total is calculated from the linked orders below.</p>
                        @error('amount')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="xl:col-span-3">
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Payment Method</label>
                        <select name="payment_method" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select Method</option>
                            <option value="Cash" {{ old('payment_method', $courierPayment->payment_method) == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Bank Transfer" {{ old('payment_method', $courierPayment->payment_method) == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="Cheque" {{ old('payment_method', $courierPayment->payment_method) == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="Card" {{ old('payment_method', $courierPayment->payment_method) == 'Card' ? 'selected' : '' }}>Card</option>
                            @if(!in_array(old('payment_method', $courierPayment->payment_method), ['', 'Cash', 'Bank Transfer', 'Cheque', 'Card'], true))
                                <option value="{{ old('payment_method', $courierPayment->payment_method) }}" selected>{{ old('payment_method', $courierPayment->payment_method) }}</option>
                            @endif
                        </select>
                        @error('payment_method')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 xl:col-span-6">
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Reference Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $courierPayment->reference_number) }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g., TXN123456">
                        @error('reference_number')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 xl:col-span-6">
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Payment Note</label>
                        <textarea name="payment_note" rows="3" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Additional notes about this payment...">{{ old('payment_note', $courierPayment->payment_note) }}</textarea>
                        @error('payment_note')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add More Orders</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Only unlinked dispatched COD orders for the selected courier can be added. Linked rows can be removed here if the payment needs correction.</p>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-5">
                        <label for="waybill" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Waybill / Order No</label>
                        <input type="text" id="waybill" onkeydown="handleWaybillKeydown(event)" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Scan or enter waybill / order number">
                    </div>
                    <div class="xl:col-span-2 flex items-end">
                        <button type="button" onclick="searchWaybill()" class="inline-flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Search
                        </button>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="manual_order_no_display" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Order No</label>
                        <input type="text" id="manual_order_no_display" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" readonly>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="manual_payment_method_display" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                        <input type="text" id="manual_payment_method_display" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" readonly>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label for="order_amount_preview" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Order Amount</label>
                        <input type="number" id="order_amount_preview" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" readonly>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="system_delivery_charge_preview" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">System Delivery Charge</label>
                        <input type="number" id="system_delivery_charge_preview" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" readonly>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="real_delivery_charge_preview" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Real Delivery Charge</label>
                        <input type="number" id="real_delivery_charge_preview" min="0" step="0.01" oninput="updateManualReceivedAmount()" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="xl:col-span-2">
                        <label for="manual_received_amount" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Received Amount</label>
                        <input type="number" id="manual_received_amount" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm font-medium text-emerald-700 dark:bg-gray-700 dark:border-gray-600 dark:text-emerald-300" readonly>
                    </div>
                    <div class="xl:col-span-2 flex items-end gap-2">
                        <button type="button" onclick="clearManualEntry()" class="inline-flex flex-1 items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Clear
                        </button>
                        <button type="button" onclick="addToTable()" class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Add
                        </button>
                    </div>
                </div>

                <input type="hidden" id="manual_order_id">
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-9">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Linked Orders</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Removing a row here will unlink it from this courier payment and return the order to dispatched status.</p>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="order_table_filter" class="sr-only">Filter table rows</label>
                        <input type="text" id="order_table_filter" oninput="filterTableRows(this.value)" placeholder="Filter linked orders..." class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Linked Orders</p>
                        <p id="selected_orders_count" class="mt-1 text-base font-semibold text-gray-900 dark:text-white">0</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Courier Commission</p>
                        <p id="total_courier_commission" class="mt-1 text-base font-semibold text-gray-900 dark:text-white">LKR 0.00</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Real Delivery</p>
                        <p id="total_real_delivery" class="mt-1 text-base font-semibold text-gray-900 dark:text-white">LKR 0.00</p>
                    </div>
                </div>
                @error('order_ids')
                    <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Waybill Number</th>
                                <th class="px-4 py-3">Order No</th>
                                <th class="px-4 py-3">Payment Method</th>
                                <th class="px-4 py-3 text-right">Order Amount</th>
                                <th class="px-4 py-3 text-right">System Delivery Charge</th>
                                <th class="px-4 py-3 text-right">Real Delivery Charge</th>
                                <th class="px-4 py-3 text-right">Courier Commission</th>
                                <th class="px-4 py-3 text-right">Received Amount</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="payment-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr id="empty-row">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No orders are linked to this courier payment.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('courier-payments.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Update Payment
                </button>
            </div>
        </div>
    </form>

    <script>
        const initialLinkedOrders = @json($linkedOrders);

        function getToast() {
            if (typeof Swal !== 'undefined') {
                return Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            }

            return null;
        }

        function showSuccess(message) {
            const toast = getToast();
            if (toast) {
                toast.fire({ icon: 'success', title: message });
            } else {
                alert('Success: ' + message);
            }
        }

        function showError(message) {
            const toast = getToast();
            if (toast) {
                toast.fire({ icon: 'error', title: message });
            } else {
                alert('Error: ' + message);
            }
        }

        function formatMoney(value) {
            const amount = Number.parseFloat(value);
            const normalized = Number.isFinite(amount) ? amount : 0;
            return `LKR ${normalized.toFixed(2)}`;
        }

        function normalizeNumber(value) {
            const amount = Number.parseFloat(value);
            return Number.isFinite(amount) ? amount : 0;
        }

        function calculateBreakdown(orderAmount, systemCharge, realCharge) {
            const normalizedOrderAmount = normalizeNumber(orderAmount);
            const normalizedSystemCharge = normalizeNumber(systemCharge);
            const normalizedRealCharge = normalizeNumber(realCharge);

            return {
                orderAmount: normalizedOrderAmount,
                systemCharge: normalizedSystemCharge,
                realCharge: normalizedRealCharge,
                commission: normalizedSystemCharge - normalizedRealCharge,
                received: normalizedOrderAmount - normalizedRealCharge,
            };
        }

        function handleWaybillKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchWaybill();
            }
        }

        function updateManualReceivedAmount() {
            const breakdown = calculateBreakdown(
                document.getElementById('order_amount_preview').value,
                document.getElementById('system_delivery_charge_preview').value,
                document.getElementById('real_delivery_charge_preview').value
            );

            document.getElementById('manual_received_amount').value = breakdown.received.toFixed(2);
        }

        function setManualPreview(order) {
            document.getElementById('manual_order_id').value = order.id || '';
            document.getElementById('manual_order_no_display').value = order.order_no || '';
            document.getElementById('manual_payment_method_display').value = order.payment_method || '';
            document.getElementById('order_amount_preview').value = normalizeNumber(order.order_amount).toFixed(2);
            document.getElementById('system_delivery_charge_preview').value = normalizeNumber(order.system_delivery_charge).toFixed(2);
            document.getElementById('real_delivery_charge_preview').value = normalizeNumber(order.real_delivery_charge).toFixed(2);
            updateManualReceivedAmount();
        }

        function clearManualEntry(clearSearch = true) {
            if (clearSearch) {
                document.getElementById('waybill').value = '';
            }

            document.getElementById('manual_order_id').value = '';
            document.getElementById('manual_order_no_display').value = '';
            document.getElementById('manual_payment_method_display').value = '';
            document.getElementById('order_amount_preview').value = '';
            document.getElementById('system_delivery_charge_preview').value = '';
            document.getElementById('real_delivery_charge_preview').value = '';
            document.getElementById('manual_received_amount').value = '';
        }

        function searchWaybill() {
            const reference = document.getElementById('waybill').value.trim();
            const courierId = document.getElementById('selected_courier_id')?.value;

            if (!reference) {
                showError('Enter a waybill or order number first.');
                return;
            }

            if (!courierId) {
                showError('Select a courier first.');
                return;
            }

            fetch(`{{ route('courier-receive.search-order') }}?courier_id=${encodeURIComponent(courierId)}&query=${encodeURIComponent(reference)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showError(data.message || 'Order not found.');
                        clearManualEntry(false);
                        return;
                    }

                    setManualPreview(data.data);
                    showSuccess(`Order found: ${data.data.waybill_number}`);
                })
                .catch(() => {
                    showError('Unable to search the selected order right now.');
                });
        }

        function addToTable() {
            const orderId = document.getElementById('manual_order_id').value;
            const waybill = document.getElementById('waybill').value.trim();
            const orderNo = document.getElementById('manual_order_no_display').value.trim();
            const paymentMethod = document.getElementById('manual_payment_method_display').value.trim();
            const orderAmount = normalizeNumber(document.getElementById('order_amount_preview').value);
            const systemCharge = normalizeNumber(document.getElementById('system_delivery_charge_preview').value);
            const realCharge = normalizeNumber(document.getElementById('real_delivery_charge_preview').value);

            if (!orderId) {
                showError('Search and load a valid dispatched order first.');
                return;
            }

            if (!waybill) {
                showError('Waybill number is required.');
                return;
            }

            if (realCharge > orderAmount) {
                showError('Real delivery charge cannot exceed the order amount.');
                return;
            }

            appendRow({
                id: Number.parseInt(orderId, 10),
                waybill_number: waybill,
                order_no: orderNo,
                payment_method: paymentMethod,
                order_amount: orderAmount,
                system_delivery_charge: systemCharge,
                real_delivery_charge: realCharge,
            });

            clearManualEntry();
            document.getElementById('waybill').focus();
        }

        function appendRow(order) {
            const tbody = document.getElementById('payment-table-body');
            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) {
                emptyRow.remove();
            }

            if (document.querySelector(`input[name="order_ids[]"][value="${order.id}"]`)) {
                showError('This order is already linked to the payment.');
                return;
            }

            const breakdown = calculateBreakdown(order.order_amount, order.system_delivery_charge, order.real_delivery_charge);
            const tr = document.createElement('tr');
            tr.className = 'bg-white transition-colors hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700';
            tr.dataset.orderId = order.id;
            tr.dataset.orderAmount = breakdown.orderAmount.toFixed(2);
            tr.dataset.systemCharge = breakdown.systemCharge.toFixed(2);
            tr.innerHTML = `
                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">${order.waybill_number}</td>
                <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">${order.order_no}</td>
                <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">${order.payment_method}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">${breakdown.orderAmount.toFixed(2)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">${breakdown.systemCharge.toFixed(2)}</td>
                <td class="px-4 py-3 text-right">
                    <input type="number" name="courier_costs[${order.id}]" value="${breakdown.realCharge.toFixed(2)}" min="0" step="0.01" class="real-delivery-charge-input block w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-right text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" oninput="recalculateRow(this.closest('tr'))">
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-200"><span class="courier-commission-value">${breakdown.commission.toFixed(2)}</span></td>
                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300"><span class="received-amount-value">${breakdown.received.toFixed(2)}</span></td>
                <td class="px-4 py-3 text-center">
                    <button type="button" onclick="removeRow(this)" class="inline-flex items-center rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700 transition hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">Delete</button>
                    <input type="hidden" name="order_ids[]" value="${order.id}">
                </td>
            `;

            tbody.appendChild(tr);
            recalculateRow(tr);
            filterTableRows(document.getElementById('order_table_filter')?.value || '');
        }

        function recalculateRow(row) {
            if (!row) {
                return true;
            }

            const orderAmount = normalizeNumber(row.dataset.orderAmount);
            const systemCharge = normalizeNumber(row.dataset.systemCharge);
            const realInput = row.querySelector('.real-delivery-charge-input');
            const realCharge = normalizeNumber(realInput?.value);

            if (realCharge > orderAmount) {
                realInput.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                row.querySelector('.received-amount-value').textContent = '0.00';
                row.querySelector('.courier-commission-value').textContent = (systemCharge - realCharge).toFixed(2);
                updateTotals();
                return false;
            }

            realInput.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            const breakdown = calculateBreakdown(orderAmount, systemCharge, realCharge);
            row.querySelector('.received-amount-value').textContent = breakdown.received.toFixed(2);
            row.querySelector('.courier-commission-value').textContent = breakdown.commission.toFixed(2);
            updateTotals();
            return true;
        }

        function updateTotals() {
            const rows = Array.from(document.querySelectorAll('#payment-table-body tr')).filter(row => row.id !== 'empty-row');
            let realDeliveryTotal = 0;
            let commissionTotal = 0;
            let receivedTotal = 0;

            rows.forEach((row) => {
                const orderAmount = normalizeNumber(row.dataset.orderAmount);
                const systemCharge = normalizeNumber(row.dataset.systemCharge);
                const realCharge = normalizeNumber(row.querySelector('.real-delivery-charge-input')?.value);
                const breakdown = calculateBreakdown(orderAmount, systemCharge, realCharge);
                realDeliveryTotal += breakdown.realCharge;
                commissionTotal += breakdown.commission;
                receivedTotal += breakdown.received;
            });

            document.getElementById('selected_orders_count').textContent = rows.length.toString();
            document.getElementById('total_real_delivery').textContent = formatMoney(realDeliveryTotal);
            document.getElementById('total_courier_commission').textContent = formatMoney(commissionTotal);
            document.getElementById('amount').value = receivedTotal.toFixed(2);
        }

        function removeRow(button) {
            button.closest('tr')?.remove();
            const tbody = document.getElementById('payment-table-body');
            if (!tbody.querySelector('tr')) {
                tbody.innerHTML = `
                    <tr id="empty-row">
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No orders are linked to this courier payment.
                        </td>
                    </tr>
                `;
            }
            updateTotals();
        }

        function filterTableRows(searchValue) {
            const query = (searchValue || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#payment-table-body tr');

            rows.forEach((row) => {
                if (row.id === 'empty-row') {
                    row.style.display = query ? 'none' : '';
                    return;
                }

                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        }

        document.getElementById('courier-payment-edit-form').addEventListener('submit', (event) => {
            const rows = Array.from(document.querySelectorAll('#payment-table-body tr')).filter(row => row.id !== 'empty-row');

            if (rows.length === 0) {
                event.preventDefault();
                showError('Keep at least one linked order on the courier payment.');
                return;
            }

            const invalidRow = rows.find((row) => !recalculateRow(row));
            if (invalidRow) {
                event.preventDefault();
                showError('One or more real delivery charges are invalid.');
            }
        });

        initialLinkedOrders.forEach((order) => appendRow(order));
        updateTotals();
    </script>
</x-app-layout>
