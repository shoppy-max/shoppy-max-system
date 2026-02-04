<x-app-layout>
    <!-- Header & Breadcrumb -->
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Edit Purchase') }}
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
                     <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('purchases.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Purchases</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('purchases.update', $purchase) }}" x-data="purchaseForm()" @submit.prevent="submitForm">
        @csrf
        @method('PUT')
        
        <div class="max-w-7xl mx-auto p-6 space-y-6">
            
            <!-- Basic Info Card -->
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">Purchase Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Supplier -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Supplier <span class="text-red-500">*</span></label>
                            <select name="supplier_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ $purchase->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->business_name ?? $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Date -->
                        <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date <span class="text-red-500">*</span></label>
                             <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                      <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input type="date" name="purchase_date" value="{{ $purchase->purchase_date->format('Y-m-d') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                            </div>
                        </div>

                        <!-- Reference -->
                         <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reference No <span class="text-red-500">*</span></label>
                             <input type="text" name="purchase_number" value="{{ $purchase->purchase_number }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                    </div>
                </div>

                <!-- Product Items -->
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Purchase Items</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add products to this purchase order</p>
                        </div>
                        <button type="button" @click="addItem()" class="text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Item
                        </button>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="relative bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-200 shadow-sm hover:shadow-md">
                                
                                <!-- Item Number Badge -->
                                <div class="absolute -top-3 -left-3 bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm shadow-lg">
                                    <span x-text="index + 1"></span>
                                </div>

                                <!-- Remove Button -->
                                <button type="button" @click="removeItem(index)" class="absolute -top-3 -right-3 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-2">
                                    
                                    <!-- Product Search - Takes more space -->
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            Product
                                        </label>
                                        <div class="relative" x-data="{ search: '', open: false, results: [] }">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                                </div>
                                                <input type="text" 
                                                       x-model="item.product_name" 
                                                       @input.debounce.300ms="
                                                            search = item.product_name;
                                                            if(search.length > 1) {
                                                                fetch(`{{ route('orders.search-products') }}?query=${search}`)
                                                                    .then(res => res.json())
                                                                    .then(data => { results = data; open = true; });
                                                            } else { open = false; }
                                                       "
                                                       @focus="if(item.product_name.length > 1) open = true"
                                                       class="bg-white dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-3 transition-all" 
                                                       placeholder="Search by name or SKU..."
                                                       required
                                                       autocomplete="off"
                                                >
                                            </div>
                                            
                                            <!-- Dropdown Results -->
                                            <div x-show="open" 
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 @click.outside="open = false" 
                                                 class="absolute z-50 w-full bg-white dark:bg-gray-800 rounded-lg shadow-2xl border-2 border-blue-200 dark:border-blue-600 mt-2 max-h-64 overflow-y-auto">
                                                
                                                <div x-show="results.length === 0" class="p-4 text-center">
                                                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No products found</p>
                                                </div>

                                                <ul x-show="results.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                                                    <template x-for="res in results" :key="res.id">
                                                        <li @click="
                                                            item.product_id = res.id;
                                                            item.product_name = res.name;
                                                            open = false;
                                                        " class="px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer transition-colors group">
                                                            <div class="flex items-center justify-between">
                                                                <div class="flex items-center gap-3 flex-1">
                                                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 flex items-center justify-center flex-shrink-0">
                                                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                                                    </div>
                                                                    <div class="flex-1 min-w-0">
                                                                        <p class="font-semibold text-gray-900 dark:text-white truncate" x-text="res.name"></p>
                                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                                            <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded" x-text="'SKU: ' + res.sku"></span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                            </div>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                            <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                                            <input type="hidden" :name="`items[${index}][product_name]`" x-model="item.product_name">
                                        </div>
                                    </div>
                                    
                                    <!-- Quantity -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                            Quantity
                                        </label>
                                        <div class="relative">
                                            <input type="number" 
                                                   step="1" 
                                                   min="1" 
                                                   x-model="item.quantity" 
                                                   :name="`items[${index}][quantity]`" 
                                                   class="bg-white dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 text-center font-semibold transition-all" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <!-- Unit Price -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Unit Price
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <span class="text-gray-500 dark:text-gray-400 font-semibold">Rs.</span>
                                            </div>
                                            <input type="number" 
                                                   step="0.01" 
                                                   min="0" 
                                                   x-model="item.purchase_price" 
                                                   :name="`items[${index}][purchase_price]`" 
                                                   class="bg-white dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-3 text-right font-semibold transition-all" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <!-- Total -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            Total
                                        </label>
                                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border-2 border-blue-200 dark:border-blue-700 rounded-lg p-3 text-right">
                                            <p class="text-lg font-bold text-blue-700 dark:text-blue-300">
                                                Rs. <span x-text="(item.quantity * item.purchase_price).toFixed(2)">0.00</span>
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div x-show="items.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium mb-2">No items added yet</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500">Click "Add Item" to start building your purchase order</p>
                        </div>
                    </div>

                    <!-- Summary Footer -->
                    <div x-show="items.length > 0" class="mt-6 pt-6 border-t-2 border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-lg p-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Total Items: <span class="text-blue-600 dark:text-blue-400" x-text="items.length"></span>
                                </span>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Subtotal (before discount)</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    Rs. <span x-text="subTotal.toFixed(2)">0.00</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">Payment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Amount Paid</label>
                             <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">Rs.</span>
                                </div>
                                <input type="number" step="0.01" name="paid_amount" x-model="paid_amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="0.00">
                            </div>
                        </div>
                        <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Method</label>
                             <select name="payment_method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <option value="Cash" {{ $purchase->payment_method == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Card" {{ $purchase->payment_method == 'Card' ? 'selected' : '' }}>Card</option>
                                <option value="Cheque" {{ $purchase->payment_method == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="Bank Transfer" {{ $purchase->payment_method == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                             </select>
                        </div>
                         <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Account</label>
                             <input type="text" name="payment_account" value="{{ $purchase->payment_account }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="e.g. Bank Acc No">
                        </div>
                        <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note / Cheque No</label>
                             <input type="text" name="payment_note" value="{{ $purchase->payment_note }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        </div>
                    </div>
                </div>

            <!-- Financial Summary -->
            <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b pb-2 dark:border-gray-700">Financial Summary</h3>
                    
                    <div class="flex justify-between mb-3 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="'Rs. ' + subTotal.toFixed(2)"></span>
                        <input type="hidden" name="sub_total" :value="subTotal">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Discount</label>
                        <div class="flex gap-2">
                            <input type="number" x-model="discount_value" name="discount_value" placeholder="0" class="w-2/3 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-right">
                            <select x-model="discount_type" name="discount_type" class="w-1/3 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="fixed">Rs.</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                        <p class="text-xs text-green-600 mt-1 text-right italic" x-show="discountAmount > 0">
                            - Rs. <span x-text="discountAmount.toFixed(2)"></span>
                        </p>
                        <input type="hidden" name="discount_amount" :value="discountAmount">
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex justify-between items-center mb-6">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Net Total</span>
                        <span class="text-xl font-extrabold text-blue-600" x-text="'Rs. ' + netTotal.toFixed(2)"></span>
                         <input type="hidden" name="net_total" :value="netTotal">
                    </div>
                    
                    <!-- Balance Indicator -->
                    <div class="mb-6 p-3 rounded-md text-sm font-medium flex justify-between items-center" 
                         :class="paid_amount >= netTotal ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        <span>Balance Due:</span>
                        <span x-text="'Rs. ' + Math.max(0, netTotal - paid_amount).toFixed(2)"></span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 shadow-md">
                            Update Purchase
                        </button>
                        <a href="{{ route('purchases.index') }}" class="w-full text-center text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 shadow-sm">
                            Cancel
                        </a>
                    </div>
                </div>
        </div>
    </form>

    <script>
        function purchaseForm() {
            return {
                items: @json($purchase->items->map(function($item){ return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'purchase_price' => $item->purchase_price
                ]; })),
                discount_type: '{{ $purchase->discount_type ?? 'fixed' }}',
                discount_value: {{ $purchase->discount_value ?? 0 }},
                paid_amount: {{ $purchase->paid_amount ?? 0 }},
                
                addItem() {
                    this.items.push({ product_id: null, product_name: '', quantity: 1, purchase_price: 0 });
                },
                
                removeItem(index) {
                    if(this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                
                get subTotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity || 0) * parseFloat(item.purchase_price || 0)), 0);
                },
                
                get discountAmount() {
                    if (this.discount_type === 'percentage') {
                        return (this.subTotal * parseFloat(this.discount_value || 0)) / 100;
                    }
                    return parseFloat(this.discount_value || 0);
                },
                
                get netTotal() {
                    let total = this.subTotal - this.discountAmount;
                    return total > 0 ? total : 0;
                },

                submitForm(e) {
                    if (this.items.some(item => !item.product_name || item.quantity <= 0)) {
                        alert('Please ensure all items have a product and valid quantity.');
                        return;
                    }
                    e.target.submit();
                }
            }
        }
    </script>
</x-app-layout>
