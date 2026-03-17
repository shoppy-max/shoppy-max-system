<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Receive Courier Payment') }} - {{ $courier->name }}
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
                            <a href="{{ route('courier-receive.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Receive Courier Payment</a>
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
        <form action="{{ route('courier-receive.store', $courier->id) }}" method="POST" enctype="multipart/form-data" id="receive-form">
            @csrf
            <input type="hidden" name="expected_amount" id="expected_amount" value="0">

            <div class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Setup</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Set the settlement account first. The received amount is calculated automatically from the selected orders.</p>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-4">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label for="payment_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Account <span class="text-red-500">*</span></label>
                            <a href="{{ route('bank-accounts.create') }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-400">Add Account</a>
                        </div>
                        <select name="payment_account_id" id="payment_account_id" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            <option value="">Select payment account</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('payment_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->display_label }}
                                </option>
                            @endforeach
                        </select>
                        @if($bankAccounts->isEmpty())
                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">No active accounts found. Please add one before saving payment.</p>
                        @endif
                        @error('payment_account_id')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="xl:col-span-3">
                        <label for="payment_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-500">*</span></label>
                        <input type="date" id="payment_date" value="{{ now()->toDateString() }}" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" disabled>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Uses the current system date automatically.</p>
                    </div>
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Orders</label>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Selected</p>
                            <p id="selected_orders_count" class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">0</p>
                        </div>
                    </div>
                    <div class="xl:col-span-3">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Received Amount</label>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-700/60 dark:bg-emerald-900/20">
                            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Calculated Total</p>
                            <p id="calculated_received_total" class="mt-1 text-lg font-semibold text-emerald-900 dark:text-emerald-100">LKR 0.00</p>
                        </div>
                        @error('expected_amount')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Search & Add Order</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Search by waybill or order number. Only dispatched COD orders for this courier that are not already reconciled can be added.</p>

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
                        <label for="order_amount" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Order Amount</label>
                        <input type="number" id="order_amount" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" readonly>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="system_delivery_charge" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">System Delivery Charge</label>
                        <input type="number" id="system_delivery_charge" class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" readonly>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="real_delivery_charge" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Real Delivery Charge</label>
                        <input type="number" id="real_delivery_charge" min="0" step="0.01" oninput="updateManualReceivedAmount()" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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

            <div class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-9">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Added Orders</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Each selected order stores the real courier charge used for this reconciliation.</p>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="order_table_filter" class="sr-only">Filter table rows</label>
                        <input type="text" id="order_table_filter" oninput="filterTableRows(this.value)" placeholder="Filter added orders..." class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Real Delivery</p>
                        <p id="total_real_delivery" class="mt-1 text-base font-semibold text-gray-900 dark:text-white">LKR 0.00</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Courier Commission</p>
                        <p id="total_courier_commission" class="mt-1 text-base font-semibold text-gray-900 dark:text-white">LKR 0.00</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Order Amount Total</p>
                        <p id="total_order_amount" class="mt-1 text-base font-semibold text-gray-900 dark:text-white">LKR 0.00</p>
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
                                    No orders added yet. Search by waybill or order number.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Excel Import</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Import waybills in bulk. Only eligible unlinked orders will be added.</p>
                    </div>
                    <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                        <input type="file" name="excel_file" id="excel_file" class="block w-full rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <button type="button" onclick="uploadExcel()" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Upload
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-lg bg-green-700 px-8 py-3 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700">
                    Save Payment
                </button>
            </div>
        </form>
    </div>

    <script>
        const initialReceiveOrders = @json($initialRows);

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
                document.getElementById('order_amount').value,
                document.getElementById('system_delivery_charge').value,
                document.getElementById('real_delivery_charge').value
            );

            document.getElementById('manual_received_amount').value = breakdown.received.toFixed(2);
        }

        function setManualPreview(order) {
            document.getElementById('manual_order_id').value = order.id || '';
            document.getElementById('manual_order_no_display').value = order.order_no || '';
            document.getElementById('manual_payment_method_display').value = order.payment_method || '';
            document.getElementById('order_amount').value = normalizeNumber(order.order_amount).toFixed(2);
            document.getElementById('system_delivery_charge').value = normalizeNumber(order.system_delivery_charge).toFixed(2);
            document.getElementById('real_delivery_charge').value = normalizeNumber(order.real_delivery_charge).toFixed(2);
            updateManualReceivedAmount();
        }

        function clearManualEntry(clearSearch = true) {
            if (clearSearch) {
                document.getElementById('waybill').value = '';
            }

            document.getElementById('manual_order_id').value = '';
            document.getElementById('manual_order_no_display').value = '';
            document.getElementById('manual_payment_method_display').value = '';
            document.getElementById('order_amount').value = '';
            document.getElementById('system_delivery_charge').value = '';
            document.getElementById('real_delivery_charge').value = '';
            document.getElementById('manual_received_amount').value = '';
        }

        function searchWaybill() {
            const reference = document.getElementById('waybill').value.trim();
            if (!reference) {
                showError('Enter a waybill or order number first.');
                return;
            }

            fetch(`{{ route('courier-receive.search-order') }}?courier_id={{ $courier->id }}&query=${encodeURIComponent(reference)}`)
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
            const orderAmount = normalizeNumber(document.getElementById('order_amount').value);
            const systemCharge = normalizeNumber(document.getElementById('system_delivery_charge').value);
            const realCharge = normalizeNumber(document.getElementById('real_delivery_charge').value);

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
                showError('This order is already added to the payment.');
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
            let orderAmountTotal = 0;
            let realDeliveryTotal = 0;
            let commissionTotal = 0;
            let receivedTotal = 0;

            rows.forEach((row) => {
                const orderAmount = normalizeNumber(row.dataset.orderAmount);
                const systemCharge = normalizeNumber(row.dataset.systemCharge);
                const realCharge = normalizeNumber(row.querySelector('.real-delivery-charge-input')?.value);
                const breakdown = calculateBreakdown(orderAmount, systemCharge, realCharge);
                orderAmountTotal += breakdown.orderAmount;
                realDeliveryTotal += breakdown.realCharge;
                commissionTotal += breakdown.commission;
                receivedTotal += breakdown.received;
            });

            document.getElementById('selected_orders_count').textContent = rows.length.toString();
            document.getElementById('total_order_amount').textContent = formatMoney(orderAmountTotal);
            document.getElementById('total_real_delivery').textContent = formatMoney(realDeliveryTotal);
            document.getElementById('total_courier_commission').textContent = formatMoney(commissionTotal);
            document.getElementById('calculated_received_total').textContent = formatMoney(receivedTotal);
            document.getElementById('expected_amount').value = receivedTotal.toFixed(2);
        }

        function removeRow(button) {
            button.closest('tr')?.remove();
            const tbody = document.getElementById('payment-table-body');
            if (!tbody.querySelector('tr')) {
                tbody.innerHTML = `
                    <tr id="empty-row">
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No orders added yet. Search by waybill or order number.
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

        function uploadExcel() {
            const formData = new FormData();
            const fileField = document.getElementById('excel_file');
            if (!fileField.files[0]) {
                showError('Please select a file first.');
                return;
            }

            const button = document.querySelector('button[onclick="uploadExcel()"]');
            const originalText = button.innerHTML;
            button.innerHTML = 'Uploading...';
            button.disabled = true;

            formData.append('excel_file', fileField.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("courier-receive.import", $courier->id) }}', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showError(data.message || 'Import failed.');
                    return;
                }

                if (!Array.isArray(data.data) || data.data.length === 0) {
                    showError('No eligible orders were found in the uploaded file.');
                    return;
                }

                data.data.forEach((order) => appendRow(order));
                fileField.value = '';
                showSuccess(`Added ${data.data.length} eligible orders from the file.`);
            })
            .catch(() => {
                showError('An error occurred during upload.');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        document.getElementById('receive-form').addEventListener('submit', (event) => {
            const rows = Array.from(document.querySelectorAll('#payment-table-body tr')).filter(row => row.id !== 'empty-row');

            if (rows.length === 0) {
                event.preventDefault();
                showError('Add at least one dispatched order before saving the payment.');
                return;
            }

            const invalidRow = rows.find((row) => !recalculateRow(row));
            if (invalidRow) {
                event.preventDefault();
                showError('One or more real delivery charges are invalid.');
            }
        });

        initialReceiveOrders.forEach((order) => appendRow(order));
        updateTotals();
    </script>
</x-app-layout>
