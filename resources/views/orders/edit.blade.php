<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Edit Order') }} {{ $order->order_number }}
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
                            <a href="{{ route('orders.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Orders</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit Order</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div x-data="orderManager({{ json_encode($orderFull) }})" class="py-6" x-cloak>
        <div class="max-w-screen-2xl mx-auto sm:px-4 lg:px-6">
            <form @submit.prevent="submitOrder">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- LEFT COLUMN -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Order Details</h3>
                                <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                      :class="form.order_type === 'reseller' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
                                      x-text="form.order_type === 'reseller' ? 'Reseller Order' : 'Direct Order'"></span>
                            </div>
                            <div x-show="isEditLocked" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-700/40 dark:bg-amber-900/20 dark:text-amber-300">
                                Core order details are locked because call or delivery processing has already started. You can update payment entries and note only.
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order Date</label>
                                    <input type="text" x-model="form.order_date" readonly class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">System-managed; not editable.</p>
                                </div>
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order ID</label>
                                    <input type="text" value="{{ $order->order_number }}" readonly class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order Type</label>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700">
                                        <input type="radio" x-model="form.order_type" value="direct" :disabled="isEditLocked" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">Direct Order</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700">
                                        <input type="radio" x-model="form.order_type" value="reseller" :disabled="isEditLocked" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">Reseller Order</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Delivery Status</label>
                                <select
                                    x-model="form.delivery_status"
                                    :disabled="isEditLocked"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="waybill_printed">Waybill printed</option>
                                    <option value="picked_from_rack">Picked from rack</option>
                                    <option value="packed">Packed</option>
                                    <option value="dispatched">Dispatched</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="returned">Returned</option>
                                </select>
                            </div>

                            <div x-show="form.order_type === 'reseller'" x-transition>
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Select Reseller Account <span class="text-red-500">*</span></label>
                                <div class="relative" @click.outside="resellers = []">
                                    <input type="text"
                                           x-model="resellerSearch"
                                           @input.debounce.300ms="searchResellers()"
                                           @focus="if ((resellerSearch || '').trim().length >= 2) { searchResellers(); }"
                                           @click="if ((resellerSearch || '').trim().length >= 2) { searchResellers(); }"
                                           placeholder="Search company, contact name, or mobile..."
                                           :disabled="isEditLocked"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">

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
                                    <button type="button" @click="clearReseller()" x-show="!isEditLocked" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <p x-show="form.order_type === 'reseller' && !form.reseller_id" class="mt-1 text-xs text-red-500">Select a reseller account to continue.</p>
                            </div>
                        </div>

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
                                               :disabled="isEditLocked"
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">

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
                                        <p x-show="showCustomerDropdown && (customerSearch || '').trim().length >= 2 && customerResults.length === 0 && !selectedCustomer" class="mt-1 text-xs text-gray-500 dark:text-gray-400">No matching customers found. Continue editing details if this is a new customer.</p>
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
                                        <button type="button" @click="clearSelectedCustomer()" x-show="!isEditLocked" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Same names can exist. Options show mobile and location to help you pick correctly.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Name <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.customer.name" :disabled="isEditLocked" placeholder="Customer Name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400" required>
                                </div>

                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Primary Mobile <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.customer.mobile" :disabled="isEditLocked" @input="form.customer.mobile = $event.target.value.replace(/\D/g, '').slice(0, 10)" placeholder="07xxxxxxxx" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400" required>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Must be exactly 10 digits (numbers only).</p>
                                </div>

                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Secondary Mobile</label>
                                    <input type="text" x-model="form.customer.landline" :disabled="isEditLocked" @input="form.customer.landline = $event.target.value.replace(/\D/g, '').slice(0, 10)" placeholder="Optional" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional, but if entered it must be 10 digits.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Address <span class="text-red-500">*</span></label>
                                    <textarea x-model="form.customer.address" :disabled="isEditLocked" rows="3" placeholder="Street layout, number..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400" required></textarea>
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
                                               :disabled="isEditLocked"
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                                               required>
                                        <div x-show="showCityDropdown && !isEditLocked" class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700 max-h-56 overflow-y-auto">
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

                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note</label>
                            <textarea x-model="form.sales_note" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Sale note..."></textarea>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 min-h-[500px]">
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
                                           :disabled="isEditLocked"
                                           @input.debounce.300ms="searchProducts()"
                                           placeholder="Search by name or SKU..."
                                           class="sm:col-span-9 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                    <button type="button" @click="productSearch='';productResults=[]" :disabled="isEditLocked" class="sm:col-span-3 text-sm font-medium px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-300 dark:border-gray-600 disabled:opacity-60 disabled:cursor-not-allowed">
                                        Clear Search
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Start typing at least 2 characters to search products.</p>

                                <div x-show="productResults.length > 0 && !isEditLocked" @click.outside="productResults = []" class="absolute z-30 w-full bg-white dark:bg-gray-700 rounded-lg shadow-xl mt-1 max-h-80 overflow-y-auto border border-gray-100 dark:border-gray-600">
                                    <ul>
                                        <template x-for="product in productResults" :key="product.id">
                                            <li @click="if (!isEditLocked) addItem(product)" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer flex items-center border-b border-gray-100 dark:border-gray-600 last:border-0 transition duration-150">
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
                                                                :disabled="isEditLocked || (parseInt(item.quantity, 10) || 1) <= 1"
                                                                class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-gray-600 dark:text-gray-300">-</button>
                                                        <input type="text"
                                                               :value="parseInt(item.quantity, 10) || 1"
                                                               readonly
                                                               class="w-14 text-center bg-gray-100 dark:bg-gray-700 border-0 text-sm font-semibold text-gray-900 dark:text-white focus:ring-0 rounded-md p-1 cursor-not-allowed select-none">
                                                        <button type="button"
                                                                @click="increaseItemQuantity(item)"
                                                                :disabled="isEditLocked || (parseInt(item.quantity, 10) || 0) >= itemMaxQuantity(item)"
                                                                class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-gray-600 dark:text-gray-300">+</button>
                                                    </div>
                                                    <div x-show="item.quantity > (item.max_stock + (item.original_qty_if_edit || 0))" class="text-xs text-red-500 mt-1">Max: <span x-text="item.max_stock + (item.original_qty_if_edit || 0)"></span></div>
                                                    <div x-show="itemMaxQuantity(item) < 1" class="text-xs text-red-500 mt-1">Out of stock. Remove this item.</div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex flex-col items-end gap-1">
                                                        <div class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-400 rounded w-24 text-center font-mono" title="Minimum Price">
                                                            <span x-text="parseFloat(item.limit_price).toFixed(2)"></span>
                                                        </div>
                                                        <input type="number" x-model="item.selling_price" :readonly="isEditLocked" class="w-24 text-right bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1.5 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white font-bold read-only:bg-gray-100 read-only:text-gray-500 dark:read-only:bg-gray-800 dark:read-only:text-gray-400" step="0.01">
                                                        <div class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-xs text-blue-600 dark:text-blue-400 rounded w-24 text-center font-mono" title="Commission">
                                                            <span x-text="(parseFloat(item.selling_price || 0) - parseFloat(item.limit_price || 0)).toFixed(2)"></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                                    <span x-text="(item.quantity * item.selling_price).toFixed(2)"></span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" @click="removeItem(index)" :disabled="isEditLocked" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50 disabled:cursor-not-allowed">
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
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Commission</label>
                                        <div class="w-full bg-gray-100 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="totalCommission"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Courier</label>
                                        <select x-model="form.courier_id" @change="onCourierChange()" :disabled="isEditLocked" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                            <option value="">Select Courier</option>
                                            @foreach($couriers as $courier)
                                                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Delivery Charge</label>
                                        <select x-model="form.courier_charge" :disabled="isEditLocked || !form.courier_id || selectedCourierRates.length === 0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                            <option value="" x-text="!form.courier_id ? 'Select courier first' : (selectedCourierRates.length === 0 ? 'No configured charges' : 'Select delivery charge')"></option>
                                            <template x-for="rate in selectedCourierRates" :key="`edit-rate-payment-${form.courier_id}-${rate}`">
                                                <option :value="rate" x-text="`LKR ${rate}`"></option>
                                            </template>
                                        </select>
                                        <p x-show="form.courier_id && selectedCourierRates.length === 0" class="mt-1 text-xs text-amber-600 dark:text-amber-400">No delivery charge values configured for selected courier.</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Discount</label>
                                        <input
                                            type="number"
                                            x-model="form.discount_value"
                                            :disabled="isEditLocked"
                                            min="0"
                                            step="0.01"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                                            :placeholder="form.discount_type === 'percentage' ? '0.00%' : '0.00'"
                                        >
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="discountHelperText"></p>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Discount Type</label>
                                        <select x-model="form.discount_type" :disabled="isEditLocked" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                            <option value="fixed">Fixed Amount</option>
                                            <option value="percentage">Percentage</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Payment Method</label>
                                        <select x-model="form.payment_method" :disabled="isEditLocked" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400">
                                            <option value="COD">Cash on Delivery (COD)</option>
                                            <option value="Online Payment">Online Payment</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Payment Status</label>
                                        <div class="w-full bg-gray-100 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 font-medium dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="paymentStatusLabel"></span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="paymentStatusHelperText"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Call Status</label>
                                        <select
                                            x-model="form.call_status"
                                            :disabled="isEditLocked"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="confirm">Confirm</option>
                                            <option value="hold">Hold</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2" x-show="form.payment_method === 'Online Payment'" x-cloak>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-medium text-gray-900 dark:text-white">Payment Entries</label>
                                            <button
                                                type="button"
                                                @click="addPaymentEntry()"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                                            >
                                                + Add Payment
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(payment, index) in form.payments" :key="`edit-payment-${index}`">
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
                                            <span x-text="discountSummaryLabel"></span>
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
                                            <span x-show="!isSubmitting">Update Order</span>
                                            <span x-show="isSubmitting">Updating...</span>
                                        </button>
                                        <button type="button" @click="clearItems()" x-show="!isEditLocked" class="w-full text-gray-700 bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:outline-none">
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
        </div>
    </div>

    <script>
        function orderManager(initialOrder) {
            return {
                isSubmitting: false,
                isEditLocked: !(
                    String(initialOrder.call_status || 'pending').toLowerCase() === 'pending'
                    && String(initialOrder.delivery_status || 'pending').toLowerCase() === 'pending'
                ),
                resellerSearch: '',
                resellers: [],
                selectedReseller: initialOrder.reseller || null,
                customerSearch: '',
                customerResults: [],
                selectedCustomer: initialOrder.customer
                    ? {
                        id: initialOrder.customer.id,
                        name: initialOrder.customer.name,
                        mobile: initialOrder.customer.mobile,
                        landline: initialOrder.customer.landline,
                        address: initialOrder.customer.address,
                        city: initialOrder.customer_city || initialOrder.customer.city || '',
                        district: initialOrder.customer_district || '',
                        province: initialOrder.customer_province || '',
                        location_label: [initialOrder.customer_city || initialOrder.customer.city || '', initialOrder.customer_district || '', initialOrder.customer_province || ''].filter(Boolean).join(' | '),
                        display_label: `${initialOrder.customer.name || ''} | ${initialOrder.customer.mobile || ''}`.trim(),
                    }
                    : null,
                showCustomerDropdown: false,
                
                productSearch: '',
                productResults: [],

                cities: @json($cities),
                citySearch: '',
                filteredCities: [],
                showCityDropdown: false,
                courierRatesMap: @json($courierRatesMap),
                
                form: {
                    order_type: initialOrder.order_type,
                    order_date: initialOrder.order_date,
                    reseller_id: initialOrder.reseller_id,
                    
                    // Fulfillment
                    courier_id: initialOrder.courier_id,
                    courier_charge: initialOrder.courier_charge,
                    discount_type: initialOrder.discount_type || 'fixed',
                    discount_value: initialOrder.discount_value ?? initialOrder.discount_amount ?? 0,
                    payment_method: initialOrder.payment_method || 'COD',
                    payment_status: initialOrder.payment_status || 'pending',
                    paid_amount: initialOrder.paid_amount || 0,
                    payments: Array.isArray(initialOrder.payments_data)
                        ? initialOrder.payments_data.map((payment) => ({
                            amount: payment?.amount ?? '',
                            date: payment?.date || '',
                            note: payment?.note || '',
                        }))
                        : [],
                    call_status: initialOrder.call_status,
                    delivery_status: initialOrder.delivery_status || 'pending',
                    sales_note: initialOrder.sales_note,

                    customer: {
                        name: initialOrder.customer.name,
                        mobile: initialOrder.customer.mobile,
                        landline: initialOrder.customer.landline,
                        address: initialOrder.customer.address,
                        city_id: initialOrder.selected_city_id || null,
                        city: initialOrder.customer_city || initialOrder.customer.city || '',
                        district: initialOrder.customer_district,
                        province: initialOrder.customer_province
                    },
                    items: initialOrder.items.map(item => {
                        const unitParts = [];
                        if (item.variant && item.variant.unit_value) {
                            unitParts.push(item.variant.unit_value);
                        }
                        if (item.variant && item.variant.unit && item.variant.unit.short_name) {
                            unitParts.push(item.variant.unit.short_name);
                        }
                        const unitLabel = unitParts.join(' ').trim();
                        const baseName = (item.product_name || `SKU ${item.sku || item.product_variant_id}`).trim();
                        const hasUnitInName = unitLabel && baseName.toLowerCase().includes(`(${unitLabel.toLowerCase()})`);
                        const name = unitLabel && !hasUnitInName
                            ? `${baseName} (${unitLabel})`
                            : baseName;

                        return {
                            id: item.product_variant_id,
                            name,
                            sku: item.sku,
                            unit_label: unitLabel,
                            quantity: item.quantity,
                            original_qty_if_edit: item.quantity, // Setup for stock logic
                            selling_price: parseFloat(item.unit_price),
                            limit_price: parseFloat(item.base_price),
                            max_stock: item.variant ? item.variant.quantity : 0, // Current stock available
                            image: null // Can't easily get image without eager loading on variant relation deep structure, optional
                        };
                    })
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
                    if (this.isEditLocked) {
                        return;
                    }
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
                    if (this.isEditLocked) {
                        return;
                    }
                    this.selectedCustomer = customer;
                    this.customerSearch = customer?.display_label || `${customer?.name || ''} | ${customer?.mobile || ''}`;
                    this.customerResults = [];
                    this.showCustomerDropdown = false;
                    this.applySelectedCustomer(customer);
                },

                clearSelectedCustomer() {
                    if (this.isEditLocked) {
                        return;
                    }
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
                        if (value === 'Online Payment' && this.totalAmountNumber > 0 && this.form.payments.length === 0) {
                            this.addPaymentEntry();
                        } else if (value !== 'Online Payment' && this.form.payments.length > 0) {
                            this.form.payments = [];
                            this.form.paid_amount = 0;
                        }
                        this.syncPaymentStatusRules();
                    });
                    this.$watch('form.discount_type', () => this.syncPaymentStatusRules());
                    this.$watch('form.delivery_status', () => this.syncPaymentStatusRules());
                    this.$watch('form.payments', () => this.syncPaymentStatusRules());
                    this.$watch('form.items', () => this.syncPaymentStatusRules());
                    this.$watch('form.discount_value', () => this.syncPaymentStatusRules());
                    this.$watch('form.courier_charge', () => this.syncPaymentStatusRules());
                    this.$watch('form.customer.mobile', (value) => {
                        if (this.selectedCustomer && String(value || '') !== String(this.selectedCustomer.mobile || '')) {
                            this.selectedCustomer = null;
                        }
                    });
                    if (this.form.payment_method === 'Online Payment' && this.totalAmountNumber > 0 && this.form.payments.length === 0) {
                        this.addPaymentEntry();
                    } else if (this.form.payment_method !== 'Online Payment' && this.form.payments.length > 0) {
                        this.form.payments = [];
                        this.form.paid_amount = 0;
                    }

                    const selected = this.cities.find((city) => String(city.id) === String(this.form.customer.city_id));
                    if (selected) {
                        this.citySearch = selected.city_name;
                        this.form.customer.city = selected.city_name;
                        this.form.customer.district = selected.district || '';
                        this.form.customer.province = selected.province || '';
                    } else {
                        this.citySearch = this.form.customer.city || '';
                    }
                    if (this.selectedCustomer) {
                        this.customerSearch = this.selectedCustomer.display_label || `${this.selectedCustomer.name || ''} | ${this.selectedCustomer.mobile || ''}`;
                    }
                    this.onCourierChange();
                    this.filterCities();
                    this.syncPaymentStatusRules();
                },

                currentDate() {
                    return new Date().toISOString().split('T')[0];
                },

                addPaymentEntry() {
                    if (this.form.payment_method !== 'Online Payment') {
                        return;
                    }
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
                    if (this.form.payment_method !== 'Online Payment') {
                        return [];
                    }

                    const normalized = [];

                    for (let i = 0; i < this.form.payments.length; i++) {
                        const payment = this.form.payments[i] || {};
                        const amount = parseFloat(payment.amount);
                        const date = (payment.date || '').toString().trim();
                        const note = (payment.note || '').toString().trim();
                        const rawAmount = (payment.amount ?? '').toString().trim();

                        if (!rawAmount && !date && !note) {
                            continue;
                        }

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
                    if (this.isEditLocked) {
                        return;
                    }
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
                    if (this.isEditLocked) {
                        return;
                    }
                    this.form.customer.city_id = city.id;
                    this.form.customer.city = city.city_name;
                    this.form.customer.district = city.district || '';
                    this.form.customer.province = city.province || '';
                    this.citySearch = city.city_name;
                    this.showCityDropdown = false;
                },
                
                // --- Search Logic ---
                async searchResellers() {
                    if (this.isEditLocked) {
                        this.resellers = [];
                        return;
                    }
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
                    this.resellers = [];
                    this.resellerSearch = '';
                },
                
                clearReseller() {
                    if (this.isEditLocked) {
                        return;
                    }
                    this.selectedReseller = null;
                    this.form.reseller_id = null;
                },
                
                async searchProducts() {
                    if (this.isEditLocked) {
                        this.productResults = [];
                        return;
                    }
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
                
                // --- Cart Logic ---
                addItem(product) {
                     if (this.isEditLocked) {
                         return;
                     }
                     if (product.stock <= 0) {
                         this.notify('warning', 'This product is out of stock.');
                         return;
                     }
                     
                     // Check if already exists
                     const existing = this.form.items.find(i => i.id === product.id);
                     if (existing) {
                         // Logic for edit: available = current_stock + original_qty (if existing item)
                         const allowedStock = product.stock + (existing.original_qty_if_edit || 0);

                         if (existing.quantity < allowedStock) {
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
                             selling_price: parseFloat(product.selling_price), // Default to current SP
                             limit_price: parseFloat(product.limit_price),
                             max_stock: product.stock,
                             image: product.image
                         });
                     }
                     this.productSearch = '';
                     this.productResults = [];
                },

                itemMaxQuantity(item) {
                    const maxStock = parseInt(item?.max_stock, 10) || 0;
                    const originalQty = parseInt(item?.original_qty_if_edit, 10) || 0;
                    return Math.max(maxStock + originalQty, 0);
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
                    if (this.isEditLocked) return;
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
                    if (this.isEditLocked) return;
                    if (!item) return;
                    this.normalizeItemQuantity(item);
                    if (item.quantity <= 1) {
                        return;
                    }

                    item.quantity -= 1;
                },
                
                removeItem(index) {
                    if (this.isEditLocked) return;
                    this.form.items.splice(index, 1);
                },

                clearItems() {
                    if (this.isEditLocked) return;
                    if (this.form.items.length === 0) {
                        return;
                    }
                    this.form.items = [];
                    this.notify('success', 'Items cleared.');
                },
                
                // --- Computed Totals ---
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
                    const discount = this.discountValueNumber;
                    if (discount <= 0) {
                        return 0;
                    }

                    if (this.form.discount_type === 'percentage') {
                        return Math.min((this.subTotal * discount) / 100, this.subTotal);
                    }

                    return Math.min(discount, this.subTotal);
                },

                get discountValueNumber() {
                    const discount = parseFloat(this.form.discount_value);
                    if (!Number.isFinite(discount) || discount <= 0) {
                        return 0;
                    }

                    return discount;
                },

                get discountHelperText() {
                    if (this.form.discount_type === 'percentage') {
                        return 'Enter a percentage from 0 to 100. The system calculates the actual discount amount.';
                    }

                    return 'Fixed discount cannot exceed subtotal.';
                },

                get discountSummaryLabel() {
                    if (this.form.discount_type === 'percentage' && this.discountValueNumber > 0) {
                        return `Discount (${this.discountValueNumber.toFixed(2)}%)`;
                    }

                    return 'Discount';
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

                get isOnlinePaymentFullyPaid() {
                    return this.form.payment_method === 'Online Payment' && this.remainingAmount <= 0;
                },

                get isPaymentStatusForcedPaid() {
                    return this.form.delivery_status === 'delivered' || this.isOnlinePaymentFullyPaid;
                },

                syncPaymentStatusRules() {
                    this.form.payment_status = this.isPaymentStatusForcedPaid ? 'paid' : 'pending';
                },

                get paymentStatusLabel() {
                    return this.form.payment_status === 'paid' ? 'Paid' : 'Pending';
                },

                get paymentStatusHelperText() {
                    if (this.form.delivery_status === 'delivered') {
                        return 'Auto-set to Paid because delivery is marked Delivered.';
                    }

                    if (this.isOnlinePaymentFullyPaid) {
                        return 'Auto-set to Paid because online payment is fully recorded.';
                    }

                    if (this.form.payment_method === 'Online Payment') {
                        return 'Pending until the recorded online payments cover the full net total.';
                    }

                    return 'Pending until delivery or courier settlement completes the collection.';
                },

                get totalCommission() {
                    if (this.form.order_type !== 'reseller') return '0.00';
                    const grossCommission = this.form.items.reduce((sum, item) => {
                        const commissionPerUnit = item.selling_price - item.limit_price;
                        return sum + (item.quantity * commissionPerUnit);
                    }, 0);

                    return Math.max(grossCommission - this.discountAmount, 0).toFixed(2);
                },
                
                // --- Submission ---
                async submitOrder() {
                    this.syncPaymentStatusRules();
                    if (!this.isEditLocked) {
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
                                this.notify('warning', `${invalidQtyItem.name} is out of stock. Remove it before updating.`);
                            } else {
                                this.notify('warning', `Invalid quantity for ${invalidQtyItem.name}.`);
                            }
                            return;
                        }
                        if (!this.form.customer.city_id) {
                            this.notify('warning', 'Please select a city from the list.');
                            return;
                        }

                        const rawDiscount = this.discountValueNumber;
                        if (this.form.discount_type === 'percentage' && rawDiscount > 100) {
                            this.notify('warning', 'Percentage discount cannot exceed 100.');
                            return;
                        }
                        if (this.form.discount_type === 'fixed' && rawDiscount > this.subTotal) {
                            this.notify('warning', 'Discount cannot exceed subtotal.');
                            return;
                        }
                        this.form.discount_value = rawDiscount.toFixed(2);
                        this.form.discount_amount = this.discountAmount.toFixed(2);
                    }

                    const normalizedPayments = this.normalizePayments();
                    if (normalizedPayments === null) {
                        return;
                    }

                    this.form.payments = normalizedPayments;
                    this.form.paid_amount = this.paidAmount.toFixed(2);

                    if (this.form.payment_method === 'Online Payment' && this.totalAmountNumber > 0 && this.form.payments.length === 0) {
                        this.notify('warning', 'Add at least one payment entry for online payment orders.');
                        return;
                    }

                    if (this.paidAmount > this.totalAmountNumber) {
                        this.notify('warning', 'Total paid amount cannot exceed net total.');
                        return;
                    }

                    if (!this.isEditLocked) {
                        const invalidPrice = this.form.items.find(item => item.selling_price < item.limit_price);
                        if (invalidPrice) {
                            this.notify('error', `Selling price for ${invalidPrice.name} cannot be lower than limit price (${invalidPrice.limit_price}).`);
                            return;
                        }
                        if (this.form.items.length === 0) {
                            this.notify('warning', 'Add at least one item before updating.');
                            return;
                        }
                    }

                    this.isSubmitting = true;
                    
                    try {
                        const response = await fetch('{{ route("orders.update", $order->id) }}', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.form)
                        });
                        
                        const result = await response.json();
                        
                        if (response.ok && result.success) {
                            // Redirect
                            window.location.href = result.redirect;
                        } else {
                            const firstValidationError = result.errors ? Object.values(result.errors).flat()[0] : null;
                            this.notify('error', firstValidationError || result.message || 'Failed to update order.');
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
