<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Breadcrumb & Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $courier->name }} Domestic Payment
                </h2>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white">
                                Home
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <a href="{{ route('couriers.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white md:ml-2">Order</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ $courier->name }} Domestic Payment</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Main Form Section -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
                <form action="{{ route('courier-receive.store', $courier->id) }}" method="POST" enctype="multipart/form-data" id="receive-form">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                        <!-- Payment Account -->
                        <div>
                            <label for="payment_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Account <span class="text-red-500">*</span></label>
                            <select name="payment_account" id="payment_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="ESB test Acccont">ESB test Acccont</option>
                                <!-- Populate dynamically if Accounts gets implemented -->
                            </select>
                        </div>

                        <!-- Date -->
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>

                        <!-- Excel Import -->
                        <div>
                             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Excel Import</label>
                             <div class="flex gap-3">
                                 <input type="file" name="excel_file" id="excel_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
<button type="button" onclick="uploadExcel()" class="text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-6 py-2.5 shadow-lg shadow-blue-500/50 dark:shadow-blue-800/80 focus:outline-none transition-all transform hover:scale-105">Upload</button>
                             </div>
                             <div class="mt-2 text-right">
                                 <a href="#" class="text-xs text-orange-600 dark:text-orange-400 hover:underline">Courier Template Download</a>
                             </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Manual Entry Section -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Manual Entry</h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-1">
                        <label for="waybill" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waybill</label>
                        <input type="text" id="waybill" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Scan or Enter Waybill">
                    </div>
                    <div class="md:col-span-1">
                        <label for="cod_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">COD Amount</label>
                        <input type="number" id="cod_amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" readonly>
                    </div>
                    <div class="md:col-span-1">
                        <label for="delivery_charge" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Delivery Charge</label>
                        <input type="number" id="delivery_charge" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" readonly>
                    </div>
                    <div class="md:col-span-1">
                        <label for="recipient" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipient</label>
                        <input type="text" id="recipient" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" readonly>
                    </div>
                    <div class="md:col-span-1">
                        <button type="button" onclick="addToTable()" class="w-full text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 shadow-lg shadow-blue-500/50 dark:shadow-blue-800/80 focus:outline-none transition-all transform hover:scale-105">
                            Add To Table
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-white uppercase bg-gray-800 dark:bg-gray-700">
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
                            <!-- Rows will be added here dynamically -->
                            <tr id="empty-row">
                                <td colspan="12" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No orders added yet. Scan a waybill or upload an Excel file.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="flex justify-end">
                 <button type="submit" form="receive-form" class="text-white bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-8 py-3 shadow-lg shadow-green-500/50 dark:shadow-green-800/80 focus:outline-none transition-all transform hover:scale-105">
                    Save Payment
                 </button>
            </div>

        </div>
    </div>

    <!-- Toast Notification Mixin -->
    <script>
        const Toast = Swal.mixin({
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

        function showSuccess(message) {
            Toast.fire({
                icon: 'success',
                title: message
            });
        }

        function showError(message) {
            Toast.fire({
                icon: 'error',
                title: message
            });
        }

        function addToTable() {
            const waybillInput = document.getElementById('waybill');
            const waybill = waybillInput.value.trim();
            
            if (!waybill) {
                showError('Please enter a Waybill Number');
                waybillInput.focus();
                return;
            }

            // Show loading state
            const btn = document.querySelector('button[onclick="addToTable()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Adding...';
            btn.disabled = true;

            fetch(`{{ route('courier-receive.search-order') }}?query=${waybill}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        appendRow(data.data);
                        showSuccess('Order added successfully');
                        waybillInput.value = ''; // Clear input
                        waybillInput.focus(); // Refocus for rapid entry
                    } else {
                        showError('Order not found!');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showError('An error occurred while searching.');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
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

            // Auto-fill manual info fields for visual verification
            document.getElementById('cod_amount').value = order.amount;
            document.getElementById('delivery_charge').value = order.delivery_fee;
            document.getElementById('recipient').value = order.customer_name;
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            const tbody = document.getElementById('payment-table-body');
            if (tbody.children.length === 0) {
                 tbody.innerHTML = `<tr id="empty-row"><td colspan="12" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No orders added yet. Scan a waybill or upload an Excel file.</td></tr>`;
            }
        }

        function uploadExcel() {
            const formData = new FormData();
            const fileField = document.getElementById('excel_file');
            
            if(!fileField.files[0]) {
                showError('Please select a file first.');
                return;
            }

            // Show loading
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
                         // Clear file input
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
