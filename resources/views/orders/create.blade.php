<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Add Order') }}
            </h2>
            
            <!-- Breadcrumb -->
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
                            <a href="{{ route('orders.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Orders</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Add Order</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div x-data="orderManager()" class="py-6" x-cloak>
        <div class="max-w-screen-2xl mx-auto sm:px-4 lg:px-6">
            
            <form @submit.prevent="submitOrder">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- LEFT COLUMN (Customer & Order Details) -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- Order Details Card -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Order Details</h3>
                                <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                      :class="form.order_type === 'reseller' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
                                      x-text="form.order_type === 'reseller' ? 'Reseller Order' : 'Direct Order'"></span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order Date</label>
                                    <input type="text" x-model="form.order_date" readonly class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Auto from {{ config('app.timezone') }}</p>
                                </div>
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order ID</label>
                                    <input type="text" value="{{ $nextOrderNumber }}" readonly class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order Type</label>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700">
                                        <input type="radio" x-model="form.order_type" value="direct" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">Direct Order</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700">
                                        <input type="radio" x-model="form.order_type" value="reseller" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">Reseller Order</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Reseller Selection -->
                            <div x-show="form.order_type === 'reseller'" x-transition>
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Select Reseller Account <span class="text-red-500">*</span></label>
                                <div class="relative" @click.outside="resellers = []">
                                    <input type="text"
                                           x-model="resellerSearch"
                                           @input.debounce.300ms="searchResellers()"
                                           @focus="if ((resellerSearch || '').trim().length >= 2) { searchResellers(); }"
                                           @click="if ((resellerSearch || '').trim().length >= 2) { searchResellers(); }"
                                           placeholder="Search company, contact name, or mobile..."
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">

                                    <div x-show="resellers.length > 0 && !selectedReseller" class="absolute z-20 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto border border-gray-100 dark:border-gray-600">
                                        <ul>
                                            <template x-for="reseller in resellers" :key="reseller.id">
                                                <li @click="selectReseller(reseller)" class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200 border-b border-gray-100 dark:border-gray-600 last:border-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="font-semibold" x-text="reseller.business_name || reseller.name"></div>
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300" x-text="reseller.type_label || (reseller.reseller_type === 'direct_reseller' ? 'Direct Reseller' : 'Reseller')"></span>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        <span x-show="reseller.business_name">Contact: </span>
                                                        <span x-show="reseller.business_name" x-text="reseller.name"></span>
                                                        <span x-show="reseller.business_name"> | </span>
                                                        <span x-text="reseller.mobile"></span>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    <p x-show="(resellerSearch || '').trim().length >= 2 && resellers.length === 0 && !selectedReseller" class="mt-1 text-xs text-gray-500 dark:text-gray-400">No matching reseller accounts found.</p>
                                </div>

                                <div x-show="selectedReseller" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800 flex justify-between items-center">
                                    <div>
                                        <div class="text-sm font-bold text-blue-800 dark:text-blue-300 flex items-center gap-2">
                                            <span x-text="selectedReseller?.business_name || selectedReseller?.name"></span>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300" x-text="selectedReseller?.type_label || (selectedReseller?.reseller_type === 'direct_reseller' ? 'Direct Reseller' : 'Reseller')"></span>
                                        </div>
                                        <div class="text-xs text-blue-600 dark:text-blue-400">
                                            <span x-show="selectedReseller?.business_name">Contact: </span>
                                            <span x-show="selectedReseller?.business_name" x-text="selectedReseller?.name"></span>
                                            <span x-show="selectedReseller?.business_name"> | </span>
                                            <span x-text="selectedReseller?.mobile"></span>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearReseller()" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <p x-show="form.order_type === 'reseller' && !form.reseller_id" class="mt-1 text-xs text-red-500">Select a reseller account to continue.</p>
                            </div>
                        </div>

                        <!-- Customer Details Card -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Customer Details</h3>
                            
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Select Existing Customer (Optional)</label>
                                    <div class="relative" @click.outside="showCustomerDropdown = false">
                                        <input type="text"
                                               x-model="customerSearch"
                                               @input.debounce.300ms="searchCustomers()"
                                               @focus="if ((customerSearch || '').trim().length >= 2) { searchCustomers(); }"
                                               @click="if ((customerSearch || '').trim().length >= 2) { searchCustomers(); }"
                                               placeholder="Search customer by name, mobile, or address..."
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">

                                        <div x-show="showCustomerDropdown && customerResults.length > 0 && !selectedCustomer" class="absolute z-30 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto border border-gray-100 dark:border-gray-600">
                                            <ul>
                                                <template x-for="customer in customerResults" :key="customer.id">
                                                    <li @click="selectCustomer(customer)" class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200 border-b border-gray-100 dark:border-gray-600 last:border-0">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <div class="font-semibold" x-text="customer.name || 'Unnamed Customer'"></div>
                                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200" x-text="customer.mobile || 'No mobile'"></span>
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="customer.location_label || customer.address || 'No address details'"></div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                        <p x-show="showCustomerDropdown && (customerSearch || '').trim().length >= 2 && customerResults.length === 0 && !selectedCustomer" class="mt-1 text-xs text-gray-500 dark:text-gray-400">No matching customers found. Continue filling details to create a new one.</p>
                                    </div>

                                    <div x-show="selectedCustomer" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800 flex justify-between items-center">
                                        <div>
                                            <div class="text-sm font-bold text-blue-800 dark:text-blue-300" x-text="selectedCustomer?.name"></div>
                                            <div class="text-xs text-blue-600 dark:text-blue-400">
                                                <span x-text="selectedCustomer?.mobile || '-'"></span>
                                                <span x-show="selectedCustomer?.location_label"> | </span>
                                                <span x-text="selectedCustomer?.location_label"></span>
                                            </div>
                                        </div>
                                        <button type="button" @click="clearSelectedCustomer()" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Same names can exist. Options show mobile and location to help you pick correctly.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Name <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.customer.name" placeholder="Customer Name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                </div>

                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Primary Mobile <span class="text-red-500">*</span></label>
                                    <input
                                        type="text"
                                        x-model="form.customer.mobile"
                                        @input="form.customer.mobile = $event.target.value.replace(/\D/g, '').slice(0, 10)"
                                        placeholder="07xxxxxxxx"
                                        inputmode="numeric"
                                        pattern="[0-9]{10}"
                                        maxlength="10"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        required
                                    >
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Must be exactly 10 digits (numbers only).</p>
                                </div>

                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Secondary Mobile</label>
                                    <input
                                        type="text"
                                        x-model="form.customer.landline"
                                        @input="form.customer.landline = $event.target.value.replace(/\D/g, '').slice(0, 10)"
                                        placeholder="Optional"
                                        inputmode="numeric"
                                        pattern="[0-9]{10}"
                                        maxlength="10"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    >
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional, but if entered it must be 10 digits.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Address <span class="text-red-500">*</span></label>
                                    <textarea x-model="form.customer.address" rows="3" placeholder="Street layout, number..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">City <span class="text-red-500">*</span></label>
                                    <div class="relative" @click.outside="showCityDropdown = false">
                                        <input type="text"
                                               x-model="citySearch"
                                               @focus="showCityDropdown = true; filterCities()"
                                               @click="showCityDropdown = true; filterCities()"
                                               @input="onCitySearchInput()"
                                               placeholder="Search city from master list..."
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                               required>
                                        <div x-show="showCityDropdown" class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700 max-h-56 overflow-y-auto">
                                            <template x-if="filteredCities.length === 0">
                                                <div class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">No cities found.</div>
                                            </template>
                                            <template x-for="city in filteredCities" :key="city.id">
                                                <button type="button" @click="selectCity(city)" class="w-full text-left px-3 py-2 hover:bg-blue-50 dark:hover:bg-gray-600">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="city.city_name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="city.district"></span>
                                                        <span> | </span>
                                                        <span x-text="city.province || '-'"></span>
                                                        <span> | </span>
                                                        <span x-text="city.postal_code || '-'"></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="hidden" x-model="form.customer.city_id">
                                    <p x-show="form.customer.city_id" class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                        Selected:
                                        <span x-text="form.customer.city"></span>
                                        <span> | </span>
                                        <span x-text="form.customer.district || '-'"></span>
                                        <span> | </span>
                                        <span x-text="form.customer.province || '-'"></span>
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">City must be selected from `Cities` master list.</p>
                                </div>

                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">District</label>
                                    <input type="text" x-model="form.customer.district" readonly class="bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                                </div>

                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Province</label>
                                    <input type="text" x-model="form.customer.province" readonly class="bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note</label>
                            <textarea x-model="form.sales_note" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Sale note..."></textarea>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN (Products & Payment) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Products Card -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 min-h-[500px]">
                            
                            <!-- Product Search Bar -->
                            <div class="mb-6 relative">
                                <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <label class="block text-sm font-medium text-gray-900 dark:text-white">Choose Product</label>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Items: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="form.items.length"></span>
                                        | Qty: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="itemsCount"></span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-12">
                                     <input type="text"
                                       x-model="productSearch"
                                       @input.debounce.300ms="searchProducts()"
                                       placeholder="Search by name or SKU..."
                                       class="sm:col-span-9 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                     <button type="button" @click="productSearch='';productResults=[]" class="sm:col-span-3 text-sm font-medium px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-300 dark:border-gray-600">
                                        Clear Search
                                     </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Start typing at least 2 characters to search products.</p>
                               
                                <!-- Search Results Dropdown -->
                                <div x-show="productResults.length > 0" @click.outside="productResults = []" class="absolute z-30 w-full bg-white dark:bg-gray-700 rounded-lg shadow-xl mt-1 max-h-80 overflow-y-auto border border-gray-100 dark:border-gray-600">
                                    <ul>
                                        <template x-for="product in productResults" :key="product.id">
                                            <li @click="addItem(product)" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer flex items-center border-b border-gray-100 dark:border-gray-600 last:border-0 transition duration-150">
                                                <div class="flex-shrink-0 h-10 w-10 mr-4">
                                                    <img :src="product.image ? (product.image.startsWith('http') ? product.image : '/storage/' + product.image) : 'https://ui-avatars.com/api/?name=' + product.name" class="h-10 w-10 rounded-lg object-cover bg-gray-100 dark:bg-gray-600">
                                                </div>
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-900 dark:text-white text-sm" x-text="product.name"></div>
                                                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        <span class="bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded mr-2" x-text="product.sku"></span>
                                                        <span x-show="product.unit_label" class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded mr-2" x-text="product.unit_label"></span>
                                                        <span :class="product.stock > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'" x-text="product.stock > 0 ? product.stock + ' in Stock' : 'Out of Stock'"></span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-bold text-gray-900 dark:text-white">Rs. <span x-text="product.selling_price"></span></div>
                                                    <div class="text-xs text-red-500 dark:text-red-400">Min: <span x-text="product.limit_price"></span></div>
                                                </div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <!-- Product Table -->
                            <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3">Image</th>
                                            <th scope="col" class="px-4 py-3">Product Name</th>
                                            <th scope="col" class="px-4 py-3 text-center">Order Quantity</th>
                                            <th scope="col" class="px-4 py-3 text-right">Selling Price</th>
                                            <th scope="col" class="px-4 py-3 text-right">Line Total</th>
                                            <th scope="col" class="px-4 py-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800">
                                        <template x-for="(item, index) in form.items" :key="item.id">
                                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                                <td class="px-4 py-3">
                                                    <img :src="item.image ? (item.image.startsWith('http') ? item.image : '/storage/' + item.image) : 'https://ui-avatars.com/api/?name=' + item.name" class="h-10 w-10 rounded-md object-cover bg-gray-100 dark:bg-gray-600">
                                                </td>
                                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                    <div x-text="item.name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                                        <span x-text="item.sku"></span>
                                                        <span x-show="item.unit_label" class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded" x-text="item.unit_label"></span>
                                                    </div>
                                                    <div x-show="item.selling_price < item.limit_price" class="text-xs text-red-500 min-w-max font-bold mt-1">
                                                        Below Limit (<span x-text="item.limit_price"></span>)
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="flex items-center justify-center space-x-1">
                                                        <button type="button"
                                                                @click="decreaseItemQuantity(item)"
                                                                :disabled="(parseInt(item.quantity, 10) || 1) <= 1"
                                                                class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-gray-600 dark:text-gray-300">-</button>
                                                        <input type="text"
                                                               :value="parseInt(item.quantity, 10) || 1"
                                                               readonly
                                                               class="w-14 text-center bg-gray-100 dark:bg-gray-700 border-0 text-sm font-semibold text-gray-900 dark:text-white focus:ring-0 rounded-md p-1 cursor-not-allowed select-none">
                                                        <button type="button"
                                                                @click="increaseItemQuantity(item)"
                                                                :disabled="(parseInt(item.quantity, 10) || 0) >= itemMaxQuantity(item)"
                                                                class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-gray-600 dark:text-gray-300">+</button>
                                                    </div>
                                                    <div x-show="item.quantity > item.max_stock" class="text-xs text-red-500 mt-1">Max: <span x-text="item.max_stock"></span></div>
                                                    <div x-show="itemMaxQuantity(item) < 1" class="text-xs text-red-500 mt-1">Out of stock. Remove this item.</div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex flex-col items-end gap-1">
                                                        <div class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-400 rounded w-24 text-center font-mono" title="Minimum Price">
                                                            <span x-text="parseFloat(item.limit_price).toFixed(2)"></span>
                                                        </div>
                                                        <input type="number" x-model="item.selling_price" class="w-24 text-right bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1.5 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white font-bold" step="0.01">
                                                        <div class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-xs text-blue-600 dark:text-blue-400 rounded w-24 text-center font-mono" title="Commission">
                                                            <span x-text="(parseFloat(item.selling_price || 0) - parseFloat(item.limit_price || 0)).toFixed(2)"></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                                    <span x-text="(item.quantity * item.selling_price).toFixed(2)"></span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="form.items.length === 0">
                                            <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                                No items added yet. Search for a product to add.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment & Summary -->
                        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                            <div class="xl:col-span-7 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-bold text-blue-700 dark:text-blue-500 mb-4">Payment Setup</h3>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Sub Total</label>
                                        <div class="w-full bg-gray-100 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="subTotal.toFixed(2)"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Discount</label>
                                        <input type="number"
                                               x-model="form.discount_amount"
                                               min="0"
                                               step="0.01"
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                               placeholder="0.00">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Discount cannot exceed subtotal.</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Courier</label>
                                        <select x-model="form.courier_id" @change="onCourierChange()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="">Select Courier</option>
                                            @foreach($couriers as $courier)
                                                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Delivery Charge</label>
                                        <select x-model="form.courier_charge" :disabled="!form.courier_id || selectedCourierRates.length === 0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                            <option value="" x-text="!form.courier_id ? 'Select courier first' : (selectedCourierRates.length === 0 ? 'No configured charges' : 'Select delivery charge')"></option>
                                            <template x-for="rate in selectedCourierRates" :key="`create-rate-${form.courier_id}-${rate}`">
                                                <option :value="rate" x-text="`LKR ${rate}`"></option>
                                            </template>
                                        </select>
                                        <p x-show="form.courier_id && selectedCourierRates.length === 0" class="mt-1 text-xs text-amber-600 dark:text-amber-400">No delivery charge values configured for selected courier.</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Payment Method</label>
                                        <select x-model="form.payment_method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="COD">Cash on Delivery (COD)</option>
                                            <option value="Online Payment">Online Payment</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-medium text-gray-900 dark:text-white">
                                                Payment Entries
                                                <span class="text-xs text-gray-500 dark:text-gray-400" x-show="form.payment_method === 'COD'">(Optional for COD)</span>
                                            </label>
                                            <button
                                                type="button"
                                                @click="addPaymentEntry()"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                                            >
                                                + Add Payment
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(payment, index) in form.payments" :key="`create-payment-${index}`">
                                                <div class="grid grid-cols-12 gap-2 rounded-lg border border-gray-200 p-2.5 bg-gray-50 dark:bg-gray-700/40 dark:border-gray-600">
                                                    <div class="col-span-4">
                                                        <input type="number"
                                                               x-model="payment.amount"
                                                               min="0.01"
                                                               step="0.01"
                                                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                               placeholder="Amount">
                                                    </div>
                                                    <div class="col-span-4">
                                                        <input type="date"
                                                               x-model="payment.date"
                                                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    </div>
                                                    <div class="col-span-3">
                                                        <input type="text"
                                                               x-model="payment.note"
                                                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                               placeholder="Note / Ref">
                                                    </div>
                                                    <div class="col-span-1 flex items-center justify-end">
                                                        <button
                                                            type="button"
                                                            @click="removePaymentEntry(index)"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-gray-700"
                                                            title="Remove payment"
                                                        >
                                                            x
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>

                                            <p x-show="form.payments.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
                                                No payment entries yet.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Call Status</label>
                                        <select x-model="form.call_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="pending">Pending</option>
                                            <option value="confirm">Confirm</option>
                                            <option value="hold">Hold</option>
                                        </select>
                                    </div>
                                    <div x-show="form.order_type === 'reseller'">
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Commission</label>
                                        <div class="w-full bg-gray-100 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="totalCommission"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="xl:col-span-5">
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 h-fit xl:sticky xl:top-24">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Summary</h3>

                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span>Line Items</span>
                                            <span class="font-semibold" x-text="form.items.length"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span>Total Quantity</span>
                                            <span class="font-semibold" x-text="itemsCount"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span>Sub Total</span>
                                            <span class="font-semibold" x-text="subTotal.toFixed(2)"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span>Discount</span>
                                            <span class="font-semibold">- <span x-text="discountAmount.toFixed(2)"></span></span>
                                        </div>
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span>Delivery</span>
                                            <span class="font-semibold" x-text="(parseFloat(form.courier_charge) || 0).toFixed(2)"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span>Paid Amount</span>
                                            <span class="font-semibold" x-text="paidAmount.toFixed(2)"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-gray-600 dark:text-gray-300">
                                            <span x-text="form.payment_method === 'COD' ? 'Remaining (COD Collect)' : 'Remaining Amount'"></span>
                                            <span class="font-semibold" x-text="remainingAmount.toFixed(2)"></span>
                                        </div>
                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex items-center justify-between text-base text-gray-900 dark:text-white">
                                            <span class="font-semibold">Net Total</span>
                                            <span class="font-bold" x-text="totalAmount"></span>
                                        </div>
                                    </div>

                                    <div class="pt-4 mt-4 space-y-2">
                                        <button type="submit"
                                                class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition duration-200"
                                                :disabled="form.items.length === 0 || isSubmitting">
                                            <span x-show="!isSubmitting">Save Order</span>
                                            <span x-show="isSubmitting">Processing...</span>
                                        </button>
                                        <button type="button" @click="clearItems()" class="w-full text-gray-700 bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:outline-none">
                                            Clear Items
                                        </button>
                                        <a href="{{ route('orders.index') }}" class="block w-full text-center text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                                            Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </form>

            <div
                x-show="showSuccessModal"
                x-transition.opacity
                @keydown.escape.window="closeSuccessModal()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
                style="display: none;"
            >
                <div @click.away="closeSuccessModal()" class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">Order created successfully</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">You can copy the order ID or continue with next action.</p>
                        </div>
                        <button
                            type="button"
                            @click="closeSuccessModal()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            aria-label="Close"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900/60 dark:bg-green-900/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-700 dark:text-green-300">Order ID</p>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-green-800 dark:text-green-200" x-text="createdOrderNumber || '-'"></p>
                            <button
                                type="button"
                                @click="copyCreatedOrderNumber()"
                                class="inline-flex items-center rounded-lg border border-green-300 bg-white px-2.5 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-gray-800 dark:text-green-300 dark:hover:bg-green-900/30"
                            >
                                Copy
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="createAnotherOrder()"
                            class="inline-flex w-1/2 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-800 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Create Another
                        </button>
                        <button
                            type="button"
                            @click="goToOrderList()"
                            class="inline-flex w-1/2 items-center justify-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700"
                        >
                            Go To Order List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Re-using the same orderManager logic from previous step, but ensuring valid JSON passed -->
    <script>
        function orderManager() {
            return {
                isSubmitting: false,
                showSuccessModal: false,
                createdOrderNumber: '',
                createdOrderRedirect: @json(route('orders.index')),
                resellerSearch: '',
                resellers: [],
                selectedReseller: null,
                customerSearch: '',
                customerResults: [],
                selectedCustomer: null,
                showCustomerDropdown: false,
                
                productSearch: '',
                productResults: [],

                cities: @json($cities),
                citySearch: '',
                filteredCities: [],
                showCityDropdown: false,
                courierRatesMap: @json($courierRatesMap),
                
                form: {
                    order_type: 'direct', 
                    order_date: @json($currentOrderDate),
                    order_status: 'pending',
                    reseller_id: null,
                    courier_id: null,
                    courier_charge: '',
                    discount_amount: 0,
                    payment_method: 'COD',
                    paid_amount: 0,
                    payments: [],
                    call_status: 'pending',
                    sales_note: '',

                    customer: {
                        name: '',
                        mobile: '',
                        landline: '',
                        address: '',
                        city_id: null,
                        city: '',
                        district: '',
                        province: ''
                    },
                    items: []
                },

                notify(type, message) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: type,
                            text: message,
                            timer: 2200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        return;
                    }
                    alert(message);
                },

                closeSuccessModal() {
                    this.showSuccessModal = false;
                },

                async copyCreatedOrderNumber() {
                    const orderNumber = (this.createdOrderNumber || '').toString().trim();
                    if (!orderNumber) {
                        return;
                    }

                    try {
                        if (navigator?.clipboard?.writeText) {
                            await navigator.clipboard.writeText(orderNumber);
                            this.notify('success', 'Order ID copied.');
                            return;
                        }
                    } catch (error) {
                        console.error('Clipboard copy failed:', error);
                    }

                    window.prompt('Copy Order ID', orderNumber);
                },

                createAnotherOrder() {
                    this.closeSuccessModal();
                    window.location.href = '{{ route("orders.create") }}';
                },

                goToOrderList() {
                    this.closeSuccessModal();
                    window.location.href = this.createdOrderRedirect || '{{ route("orders.index") }}';
                },

                findCityMatchForCustomer(customer) {
                    const cityName = (customer?.city || '').toString().trim().toLowerCase();
                    if (!cityName) {
                        return null;
                    }

                    const district = (customer?.district || '').toString().trim().toLowerCase();
                    const province = (customer?.province || '').toString().trim().toLowerCase();
                    const source = Array.isArray(this.cities) ? this.cities : [];

                    return source.find((city) => {
                        const cityMatches = (city.city_name || '').toString().trim().toLowerCase() === cityName;
                        if (!cityMatches) {
                            return false;
                        }

                        if (district && (city.district || '').toString().trim().toLowerCase() !== district) {
                            return false;
                        }

                        if (province && (city.province || '').toString().trim().toLowerCase() !== province) {
                            return false;
                        }

                        return true;
                    }) || source.find((city) => (city.city_name || '').toString().trim().toLowerCase() === cityName) || null;
                },

                applySelectedCustomer(customer) {
                    this.form.customer.name = customer?.name || '';
                    this.form.customer.mobile = customer?.mobile || '';
                    this.form.customer.landline = customer?.landline || '';
                    this.form.customer.address = customer?.address || '';

                    const matchedCity = this.findCityMatchForCustomer(customer);
                    if (matchedCity) {
                        this.form.customer.city_id = matchedCity.id;
                        this.form.customer.city = matchedCity.city_name || '';
                        this.form.customer.district = matchedCity.district || '';
                        this.form.customer.province = matchedCity.province || '';
                        this.citySearch = matchedCity.city_name || '';
                    } else {
                        this.form.customer.city_id = null;
                        this.form.customer.city = customer?.city || '';
                        this.form.customer.district = customer?.district || '';
                        this.form.customer.province = customer?.province || '';
                        this.citySearch = customer?.city || '';
                        this.notify('warning', 'Selected customer city was not found in city master. Please choose the city from the dropdown.');
                    }

                    this.filterCities();
                },

                async searchCustomers() {
                    const query = (this.customerSearch || '').trim();
                    if (query.length < 2) {
                        this.customerResults = [];
                        this.showCustomerDropdown = false;
                        return;
                    }

                    try {
                        const response = await fetch(`/orders/search-customers?q=${encodeURIComponent(query)}`);
                        this.customerResults = await response.json();
                        this.showCustomerDropdown = true;
                    } catch (error) {
                        console.error('Error searching customers:', error);
                    }
                },

                selectCustomer(customer) {
                    this.selectedCustomer = customer;
                    this.customerSearch = customer?.display_label || `${customer?.name || ''} | ${customer?.mobile || ''}`;
                    this.customerResults = [];
                    this.showCustomerDropdown = false;
                    this.applySelectedCustomer(customer);
                },

                clearSelectedCustomer() {
                    this.selectedCustomer = null;
                    this.customerSearch = '';
                    this.customerResults = [];
                    this.showCustomerDropdown = false;
                },
                
                init() {
                    this.$watch('form.order_type', (val) => {
                        if (val !== 'reseller') {
                            this.selectedReseller = null;
                            this.form.reseller_id = null;
                            this.resellerSearch = '';
                            this.resellers = [];
                        }
                    });
                    this.$watch('form.courier_id', () => this.onCourierChange());
                    this.$watch('form.payment_method', (value) => {
                        if (value === 'Online Payment' && this.form.payments.length === 0) {
                            this.addPaymentEntry();
                        }
                        this.syncOrderStatusLock();
                    });
                    this.$watch('form.discount_amount', () => this.syncOrderStatusLock());
                    this.$watch('form.items', () => this.syncOrderStatusLock());
                    this.$watch('form.customer.mobile', (value) => {
                        if (this.selectedCustomer && String(value || '') !== String(this.selectedCustomer.mobile || '')) {
                            this.selectedCustomer = null;
                        }
                    });
                    if (this.form.payment_method === 'Online Payment' && this.form.payments.length === 0) {
                        this.addPaymentEntry();
                    }
                    this.filterCities();
                    this.syncOrderStatusLock();
                },

                currentDate() {
                    return new Date().toISOString().split('T')[0];
                },

                addPaymentEntry() {
                    this.form.payments.push({
                        amount: '',
                        date: this.currentDate(),
                        note: '',
                    });
                },

                removePaymentEntry(index) {
                    this.form.payments.splice(index, 1);
                },

                normalizePayments() {
                    const normalized = [];

                    for (let i = 0; i < this.form.payments.length; i++) {
                        const payment = this.form.payments[i] || {};
                        const amount = parseFloat(payment.amount);
                        const date = (payment.date || '').toString().trim();
                        const note = (payment.note || '').toString().trim();

                        if (!Number.isFinite(amount) || amount <= 0) {
                            this.notify('warning', `Enter a valid payment amount for entry ${i + 1}.`);
                            return null;
                        }

                        if (!date) {
                            this.notify('warning', `Select payment date for entry ${i + 1}.`);
                            return null;
                        }

                        normalized.push({
                            amount: amount.toFixed(2),
                            date,
                            note,
                        });
                    }

                    return normalized;
                },

                normalizeRate(rate) {
                    if (rate === null || rate === '') {
                        return '';
                    }
                    const parsed = Number(rate);
                    if (!Number.isFinite(parsed) || parsed < 0) {
                        return '';
                    }
                    return parsed.toFixed(2);
                },

                get selectedCourierRates() {
                    const id = String(this.form.courier_id || '');
                    const rates = this.courierRatesMap[id] || [];
                    return Array.isArray(rates) ? rates : [];
                },

                onCourierChange() {
                    if (!this.form.courier_id) {
                        this.form.courier_charge = '';
                        return;
                    }

                    const rates = this.selectedCourierRates;
                    const normalizedCurrent = this.normalizeRate(this.form.courier_charge);
                    if (rates.length === 0) {
                        this.form.courier_charge = '';
                        return;
                    }

                    this.form.courier_charge = rates.includes(normalizedCurrent) ? normalizedCurrent : '';
                },
                
                filterCities() {
                    const q = (this.citySearch || '').trim().toLowerCase();
                    const source = Array.isArray(this.cities) ? this.cities : [];

                    this.filteredCities = source
                        .filter((city) => {
                            if (!q) return true;
                            const haystack = `${city.city_name || ''} ${city.district || ''} ${city.province || ''} ${city.postal_code || ''}`.toLowerCase();
                            return haystack.includes(q);
                        })
                        .slice(0, 30);
                },

                onCitySearchInput() {
                    if ((this.citySearch || '').trim() !== (this.form.customer.city || '')) {
                        this.form.customer.city_id = null;
                        this.form.customer.city = '';
                        this.form.customer.district = '';
                        this.form.customer.province = '';
                    }
                    this.showCityDropdown = true;
                    this.filterCities();
                },

                selectCity(city) {
                    this.form.customer.city_id = city.id;
                    this.form.customer.city = city.city_name;
                    this.form.customer.district = city.district || '';
                    this.form.customer.province = city.province || '';
                    this.citySearch = city.city_name;
                    this.showCityDropdown = false;
                },
                
                async searchResellers() {
                    if (this.form.order_type !== 'reseller') {
                        this.resellers = [];
                        return;
                    }

                    const query = (this.resellerSearch || '').trim();
                    if (query.length < 2) {
                        this.resellers = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/orders/search-resellers?q=${encodeURIComponent(query)}`);
                        this.resellers = await response.json();
                    } catch (error) {
                        console.error('Error searching resellers:', error);
                    }
                },
                
                selectReseller(reseller) {
                    this.selectedReseller = reseller;
                    this.form.reseller_id = reseller.id;
                    this.form.order_type = 'reseller';
                    this.resellers = [];
                    this.resellerSearch = '';
                },
                
                clearReseller() {
                    this.selectedReseller = null;
                    this.form.reseller_id = null;
                },
                
                async searchProducts() {
                    const query = (this.productSearch || '').trim();
                    if (query.length < 2) {
                        this.productResults = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/orders/search-products?q=${encodeURIComponent(query)}`);
                        this.productResults = await response.json();
                    } catch (error) {
                        console.error('Error searching products:', error);
                    }
                },
                
                addItem(product) {
                     if (product.stock <= 0) {
                         this.notify('warning', 'This product is out of stock.');
                         return;
                     }
                     const existing = this.form.items.find(i => i.id === product.id);
                     if (existing) {
                         if (existing.quantity < product.stock) {
                             existing.quantity++;
                         } else {
                             this.notify('warning', 'Max stock reached for this item.');
                         }
                     } else {
                         this.form.items.push({
                             id: product.id,
                             name: product.display_name || product.name,
                             sku: product.sku,
                             unit_label: product.unit_label || '',
                             quantity: 1,
                             selling_price: parseFloat(product.selling_price),
                             limit_price: parseFloat(product.limit_price),
                             max_stock: product.stock,
                             image: product.image
                         });
                     }
                     this.productSearch = '';
                     this.productResults = [];
                },

                itemMaxQuantity(item) {
                    return Math.max(parseInt(item?.max_stock, 10) || 0, 0);
                },

                normalizeItemQuantity(item, notifyIfAdjusted = false) {
                    if (!item) return;

                    const max = this.itemMaxQuantity(item);
                    let qty = parseInt(item.quantity, 10);
                    if (!Number.isFinite(qty)) qty = 1;
                    if (qty < 1) qty = 1;
                    if (max > 0 && qty > max) {
                        qty = max;
                        if (notifyIfAdjusted) {
                            this.notify('warning', `Max stock reached for ${item.name}.`);
                        }
                    }

                    item.quantity = qty;
                },

                increaseItemQuantity(item) {
                    if (!item) return;
                    this.normalizeItemQuantity(item);
                    const max = this.itemMaxQuantity(item);
                    if (max <= 0 || item.quantity >= max) {
                        this.notify('warning', `Max stock reached for ${item.name}.`);
                        return;
                    }

                    item.quantity += 1;
                },

                decreaseItemQuantity(item) {
                    if (!item) return;
                    this.normalizeItemQuantity(item);
                    if (item.quantity <= 1) {
                        return;
                    }

                    item.quantity -= 1;
                },
                
                removeItem(index) {
                    this.form.items.splice(index, 1);
                },

                clearItems() {
                    if (this.form.items.length === 0) {
                        return;
                    }
                    this.form.items = [];
                    this.notify('success', 'Items cleared.');
                },

                get itemsCount() {
                    return this.form.items.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 0), 0);
                },
                
                get subTotal() {
                    return this.form.items.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
                },

                get totalAmount() {
                    return this.totalAmountNumber.toFixed(2);
                },

                get totalAmountNumber() {
                    const sub = this.subTotal;
                    const discount = this.discountAmount;
                    const courier = parseFloat(this.form.courier_charge) || 0;
                    return Math.max(sub - discount, 0) + courier;
                },

                get discountAmount() {
                    const discount = parseFloat(this.form.discount_amount);
                    if (!Number.isFinite(discount) || discount <= 0) {
                        return 0;
                    }

                    return Math.min(discount, this.subTotal);
                },

                get paidAmount() {
                    return (this.form.payments || []).reduce((sum, payment) => {
                        const amount = parseFloat(payment?.amount);
                        return sum + (Number.isFinite(amount) && amount > 0 ? amount : 0);
                    }, 0);
                },

                get remainingAmount() {
                    const remaining = this.totalAmountNumber - this.paidAmount;
                    return remaining > 0 ? remaining : 0;
                },

                get isStatusLockedToPending() {
                    return this.form.payment_method === 'Online Payment' || this.discountAmount > 0;
                },

                syncOrderStatusLock() {
                    if (this.isStatusLockedToPending) {
                        this.form.order_status = 'pending';
                    }
                },
                
                get totalCommission() {
                    if (this.form.order_type !== 'reseller') return '0.00';
                    return this.form.items.reduce((sum, item) => {
                        const commissionPerUnit = item.selling_price - item.limit_price;
                        return sum + (item.quantity * commissionPerUnit);
                    }, 0).toFixed(2);
                },
                
                async submitOrder() {
                    this.syncOrderStatusLock();
                    if (this.form.order_type === 'reseller' && !this.form.reseller_id) {
                        this.notify('warning', 'Please select a reseller.');
                        return;
                    }
                    if (this.form.courier_id) {
                        const rates = this.selectedCourierRates;
                        if (rates.length === 0) {
                            this.notify('warning', 'Selected courier has no configured delivery charges.');
                            return;
                        }
                        const selectedCharge = this.normalizeRate(this.form.courier_charge);
                        if (!selectedCharge || !rates.includes(selectedCharge)) {
                            this.notify('warning', 'Select a delivery charge from the selected courier charge list.');
                            return;
                        }
                        this.form.courier_charge = selectedCharge;
                    } else {
                        this.form.courier_charge = 0;
                    }

                    this.form.items.forEach((item) => this.normalizeItemQuantity(item, true));
                    const invalidQtyItem = this.form.items.find((item) => {
                        const qty = parseInt(item.quantity, 10) || 0;
                        const max = this.itemMaxQuantity(item);
                        return max < 1 || qty < 1 || qty > max;
                    });
                    if (invalidQtyItem) {
                        if (this.itemMaxQuantity(invalidQtyItem) < 1) {
                            this.notify('warning', `${invalidQtyItem.name} is out of stock. Remove it before saving.`);
                        } else {
                            this.notify('warning', `Invalid quantity for ${invalidQtyItem.name}.`);
                        }
                        return;
                    }

                    const invalidPrice = this.form.items.find(item => item.selling_price < item.limit_price);
                    if (invalidPrice) {
                        this.notify('error', `Selling price for ${invalidPrice.name} cannot be lower than limit price (${invalidPrice.limit_price}).`);
                        return;
                    }
                    if (this.form.items.length === 0) {
                        this.notify('warning', 'Add at least one item before saving.');
                        return;
                    }
                    if (!this.form.customer.city_id) {
                        this.notify('warning', 'Please select a city from the list.');
                        return;
                    }

                    const rawDiscount = parseFloat(this.form.discount_amount);
                    if (Number.isFinite(rawDiscount) && rawDiscount > this.subTotal) {
                        this.notify('warning', 'Discount cannot exceed subtotal.');
                        return;
                    }
                    this.form.discount_amount = this.discountAmount.toFixed(2);

                    const normalizedPayments = this.normalizePayments();
                    if (normalizedPayments === null) {
                        return;
                    }

                    this.form.payments = normalizedPayments;
                    this.form.paid_amount = this.paidAmount.toFixed(2);

                    if (this.form.payment_method === 'Online Payment' && this.form.payments.length === 0) {
                        this.notify('warning', 'Add at least one payment entry for online payment orders.');
                        return;
                    }

                    if (this.paidAmount > this.totalAmountNumber) {
                        this.notify('warning', 'Total paid amount cannot exceed net total.');
                        return;
                    }
                    
                    this.isSubmitting = true;
                    try {
                        const response = await fetch('{{ route("orders.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.form)
                        });
                        const result = await response.json();
                        if (response.ok && result.success) {
                            this.createdOrderNumber = result.order_number || '';
                            this.createdOrderRedirect = result.redirect || '{{ route("orders.index") }}';
                            this.showSuccessModal = true;
                            this.isSubmitting = false;
                        } else {
                            const firstValidationError = result.errors ? Object.values(result.errors).flat()[0] : null;
                            this.notify('error', firstValidationError || result.message || 'Failed to save order.');
                            this.isSubmitting = false;
                        }
                    } catch (error) {
                        console.error('Submission error:', error);
                        this.notify('error', 'An unexpected error occurred.');
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
