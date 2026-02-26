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
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('courier-receive.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Receive Courier Payment</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">{{ $courier->name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800">
        <form action="{{ route('courier-receive.store', $courier->id) }}" method="POST" enctype="multipart/form-data" id="receive-form">
            @csrf

            <!-- Payment Setup -->
            <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Setup</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Configure payment meta details before adding orders.</p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label for="payment_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Account <span class="text-red-500">*</span></label>
                            <a href="{{ route('bank-accounts.create') }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-400">Add Account</a>
                        </div>
                        <select name="payment_account_id" id="payment_account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
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
                    <div>
                        <label for="payment_date" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    </div>
                    <div>
                        <label for="excel_file" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Excel Import</label>
                        <div class="flex gap-2">
                            <input type="file" name="excel_file" id="excel_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <button type="button" onclick="uploadExcel()" class="inline-flex items-center text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700">
                                Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Manual Entry (Two Rows) -->
            <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Search & Add Order</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Find an order by waybill, verify values, then add it to the table.</p>

                <!-- Row 1 -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 mb-4">
                    <div class="lg:col-span-7">
                        <label for="waybill" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Waybill</label>
                        <input type="text" id="waybill" onkeydown="handleWaybillKeydown(event)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Scan or enter waybill number">
                    </div>
                    <div class="lg:col-span-2 flex items-end">
                        <button type="button" onclick="searchWaybill()" class="w-full inline-flex items-center justify-center text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700">
                            Search
                        </button>
                    </div>
                    <div class="lg:col-span-3">
                        <label for="recipient" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Recipient</label>
                        <input type="text" id="recipient" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-3">
                        <label for="cod_amount" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">COD Amount</label>
                        <input type="number" id="cod_amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="lg:col-span-3">
                        <label for="delivery_charge" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Delivery Charge</label>
                        <input type="number" id="delivery_charge" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="lg:col-span-3 flex items-end">
                        <button type="button" onclick="clearManualEntry()" class="w-full inline-flex items-center justify-center text-gray-700 bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2.5 focus:outline-none dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Clear Entry
                        </button>
                    </div>
                    <div class="lg:col-span-3 flex items-end">
                        <button type="button" onclick="addToTable()" class="w-full inline-flex items-center justify-center text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700">
                            Add To Table
                        </button>
                    </div>
                </div>

                <input type="hidden" id="manual_order_id">
                <input type="hidden" id="manual_phone1">
                <input type="hidden" id="manual_phone2">
                <input type="hidden" id="manual_city">
                <input type="hidden" id="manual_remarks">
                <input type="hidden" id="manual_order_no">
                <input type="hidden" id="manual_destination">
                <input type="hidden" id="manual_description">
            </div>

            <!-- Data Table -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Added Orders</h4>
                        <div class="w-full md:w-80">
                            <input type="text" id="order_table_filter" oninput="filterTableRows(this.value)" placeholder="Filter table rows..." class="block w-full p-2.5 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">Waybill Number</th>
                                <th scope="col" class="px-4 py-3">Order No</th>
                                <th scope="col" class="px-4 py-3">Customer Name</th>
                                <th scope="col" class="px-4 py-3">Destination</th>
                                <th scope="col" class="px-4 py-3">Order Description</th>
                                <th scope="col" class="px-4 py-3">Customer Phone 1</th>
                                <th scope="col" class="px-4 py-3">Customer Phone 2</th>
                                <th scope="col" class="px-4 py-3">Delivery Fee</th>
                                <th scope="col" class="px-4 py-3">Amount</th>
                                <th scope="col" class="px-4 py-3">City</th>
                                <th scope="col" class="px-4 py-3">Remarks</th>
                                <th scope="col" class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody id="payment-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr id="empty-row">
                                <td colspan="12" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No orders added yet. Search by waybill or upload an Excel file.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-8 py-3 focus:outline-none dark:bg-green-600 dark:hover:bg-green-700">
                    Save Payment
                </button>
            </div>
        </form>
    </div>

    <script>
        // Helper to get Toast or fallback
        function getToast() {
            if (typeof Swal !== 'undefined') {
                return Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
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

        function handleWaybillKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchWaybill();
            }
        }

        function searchWaybill() {
            const waybill = document.getElementById('waybill').value.trim();
            if (!waybill) return;

            fetch(`{{ route('courier-receive.search-order') }}?query=${encodeURIComponent(waybill)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.data;
                        document.getElementById('manual_order_id').value = order.id;
                        document.getElementById('cod_amount').value = order.amount;
                        document.getElementById('delivery_charge').value = order.delivery_fee;
                        document.getElementById('recipient').value = order.customer_name;
                        
                        document.getElementById('manual_order_no').value = order.order_no;
                        document.getElementById('manual_phone1').value = order.phone1;
                        document.getElementById('manual_phone2').value = order.phone2;
                        document.getElementById('manual_city').value = order.city;
                        document.getElementById('manual_destination').value = order.destination;
                        document.getElementById('manual_description').value = order.description;
                        document.getElementById('manual_remarks').value = order.remarks;

                        showSuccess('Order found: ' + order.waybill_number);
                    } else {
                        showError('Order not found!');
                        clearManualEntry(false);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showError('Validation Error: ' + err.message);
                });
        }

        function addToTable() {
            const waybill = document.getElementById('waybill').value.trim();
            const orderId = document.getElementById('manual_order_id').value;

            if (!waybill) {
                showError('Please enter a Waybill Number');
                return;
            }
            if (!orderId) {
                showError('Please search and select a valid waybill first.');
                return;
            }

            // Construct order object from current input values (allowing for edits)
            const order = {
                id: orderId,
                waybill_number: waybill,
                order_no: document.getElementById('manual_order_no').value,
                customer_name: document.getElementById('recipient').value, 
                destination: document.getElementById('manual_destination').value,
                description: document.getElementById('manual_description').value,
                phone1: document.getElementById('manual_phone1').value,
                phone2: document.getElementById('manual_phone2').value,
                delivery_fee: document.getElementById('delivery_charge').value, 
                amount: document.getElementById('cod_amount').value, 
                city: document.getElementById('manual_city').value,
                remarks: document.getElementById('manual_remarks').value
            };

            appendRow(order);
            clearManualEntry();
            document.getElementById('waybill').focus();
        }

        function clearManualEntry(clearWaybill = true) {
            if (clearWaybill) {
                document.getElementById('waybill').value = '';
            }
            document.getElementById('manual_order_id').value = '';
            document.getElementById('cod_amount').value = '';
            document.getElementById('delivery_charge').value = '';
            document.getElementById('recipient').value = '';
            document.getElementById('manual_order_no').value = '';
            document.getElementById('manual_phone1').value = '';
            document.getElementById('manual_phone2').value = '';
            document.getElementById('manual_city').value = '';
            document.getElementById('manual_destination').value = '';
            document.getElementById('manual_description').value = '';
            document.getElementById('manual_remarks').value = '';
        }

        function appendRow(order) {
            const tbody = document.getElementById('payment-table-body');
            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.remove();

            // Check if already exists
            if (document.querySelector(`input[name="order_ids[]"][value="${order.id}"]`)) {
                showError('Order already added!');
                return;
            }

            const tr = document.createElement('tr');
            tr.className = "bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors";
            tr.innerHTML = `
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">${order.waybill_number}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.order_no}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.customer_name}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.destination}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.description}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.phone1}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.phone2}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.delivery_fee}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.amount}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.city}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${order.remarks || '-'}</td>
                <td class="px-4 py-3">
                    <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium text-sm">Remove</button>
                    <input type="hidden" name="order_ids[]" value="${order.id}">
                </td>
            `;
            tbody.appendChild(tr);
            filterTableRows(document.getElementById('order_table_filter')?.value || '');
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            const tbody = document.getElementById('payment-table-body');
            if (tbody.children.length === 0) {
                 tbody.innerHTML = `<tr id="empty-row"><td colspan="12" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No orders added yet. Search by waybill or upload an Excel file.</td></tr>`;
            }
        }

        function filterTableRows(searchValue) {
            const query = (searchValue || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#payment-table-body tr');

            rows.forEach((row) => {
                if (row.id === 'empty-row') {
                    row.style.display = query ? 'none' : '';
                    return;
                }

                const content = row.textContent.toLowerCase();
                row.style.display = content.includes(query) ? '' : 'none';
            });
        }

        function uploadExcel() {
            const formData = new FormData();
            const fileField = document.getElementById('excel_file');
            
            if(!fileField.files[0]) {
                showError('Please select a file first.');
                return;
            }

            const btn = document.querySelector('button[onclick="uploadExcel()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Uploading...';
            btn.disabled = true;

            formData.append('excel_file', fileField.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("courier-receive.import", $courier->id) }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    if (data.data.length > 0) {
                         data.data.forEach(order => appendRow(order));
                         showSuccess(`Successfully imported ${data.data.length} orders.`);
                         fileField.value = '';
                    } else {
                        showError('No matching orders found in the file.');
                    }
                } else {
                    showError(data.message || 'Import failed.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred during upload.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    </script>
</x-app-layout>
