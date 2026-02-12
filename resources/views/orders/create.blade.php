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
        <div class="max-w-[1700px] mx-auto sm:px-4 lg:px-6">
            
            <form @submit.prevent="submitOrder">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- LEFT COLUMN (Customer & Order Details) -->
                    <div class="lg:col-span-4 space-y-6">
                        
                        <!-- Order Details Card -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Order Details</h3>
                            
                            <!-- Order Date -->
                            <div class="mb-4">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order Date</label>
                                <input type="date" x-model="form.order_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            </div>

                            <!-- Order ID -->
                            <div class="mb-4">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Order ID</label>
                                <input type="text" value="{{ $nextOrderNumber }}" readonly class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400">
                            </div>

                            <!-- Sales Users (Reseller Selection) -->
                            <div class="mb-4">
                                <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Sales Users / Reseller</label>
                                <div class="relative">
                                    <input type="text" 
                                           x-model="resellerSearch" 
                                           @input.debounce.300ms="searchResellers()" 
                                           placeholder="Search Reseller..." 
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                    
                                    <!-- Dropdown -->
                                    <div x-show="resellers.length > 0 && !selectedReseller" @click.outside="resellers = []" class="absolute z-20 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto border border-gray-100 dark:border-gray-600">
                                        <ul>
                                            <template x-for="reseller in resellers" :key="reseller.id">
                                                <li @click="selectReseller(reseller)" class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                                    <div class="font-semibold" x-text="reseller.name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="reseller.mobile"></div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Selected Reseller Badge -->
                                <div x-show="selectedReseller" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800 flex justify-between items-center animate-fade-in-down">
                                    <div>
                                        <div class="text-sm font-bold text-blue-800 dark:text-blue-300" x-text="selectedReseller?.name"></div>
                                        <div class="text-xs text-blue-600 dark:text-blue-400" x-text="selectedReseller?.mobile"></div>
                                    </div>
                                    <button type="button" @click="clearReseller()" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" x-model="isResellerOrder" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-2">Mark as Reseller Order</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Details Card -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Customer Details</h3>
                            
                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Name <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.customer.name" placeholder="Customer Name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                </div>

                                <!-- Primary Mobile -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Primary Mobile <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.customer.mobile" placeholder="07xxxxxxxx" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                </div>

                                <!-- Secondary Mobile -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Secondary Mobile</label>
                                    <input type="text" x-model="form.customer.landline" placeholder="Optional" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>

                                <!-- Address -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Address <span class="text-red-500">*</span></label>
                                    <textarea x-model="form.customer.address" rows="3" placeholder="Street layout, number..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required></textarea>
                                </div>

                                <!-- City -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">City <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.customer.city" placeholder="City" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>

                                <!-- District -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">District <span class="text-red-500">*</span></label>
                                    <select x-model="form.customer.district" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                        <option value="">Select District</option>
                                        <template x-for="dist in availableDistricts" :key="dist">
                                            <option :value="dist" x-text="dist"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Country -->
                                <div>
                                    <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Country <span class="text-red-500">*</span></label>
                                    <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                        <option value="Sri Lanka">Sri Lanka</option>
                                    </select>
                                </div>
                                
                                <!-- Hidden Province Field -->
                                <div class="hidden">
                                     <select x-model="form.customer.province">
                                        <option value="">Select Province</option>
                                         <template x-for="(dists, prov) in provinces" :key="prov">
                                            <option :value="prov" x-text="prov"></option>
                                        </template>
                                     </select>
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
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Products Card -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 min-h-[500px]">
                            
                            <!-- Product Search Bar -->
                            <div class="mb-6 relative">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Choose Product</label>
                                <div class="flex">
                                     <input type="text" 
                                       x-model="productSearch" 
                                       @input.debounce.300ms="searchProducts()" 
                                       placeholder="Search by Name or SKU..." 
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pl-4 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                     <button type="button" class="ml-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg border border-gray-300 dark:border-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                     </button>
                                </div>
                               
                                <!-- Search Results Dropdown -->
                                <div x-show="productResults.length > 0" @click.outside="productResults = []" class="absolute z-30 w-full bg-white dark:bg-gray-700 rounded-lg shadow-xl mt-1 max-h-80 overflow-y-auto border border-gray-100 dark:border-gray-600">
                                    <ul>
                                        <template x-for="product in productResults" :key="product.id">
                                            <li @click="addItem(product)" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer flex items-center border-b border-gray-100 dark:border-gray-600 last:border-0 transition duration-150">
                                                <div class="flex-shrink-0 h-10 w-10 mr-4">
                                                    <img :src="product.image ? '/storage/' + product.image : 'https://ui-avatars.com/api/?name=' + product.name" class="h-10 w-10 rounded-lg object-cover bg-gray-100 dark:bg-gray-600">
                                                </div>
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-900 dark:text-white text-sm" x-text="product.name"></div>
                                                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        <span class="bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded mr-2" x-text="product.sku"></span>
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
                                                    <img :src="item.image ? '/storage/' + item.image : 'https://ui-avatars.com/api/?name=' + item.name" class="h-10 w-10 rounded-md object-cover bg-gray-100 dark:bg-gray-600">
                                                </td>
                                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                    <div x-text="item.name"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="item.sku"></div>
                                                    <div x-show="item.selling_price < item.limit_price" class="text-xs text-red-500 min-w-max font-bold mt-1">
                                                        Below Limit (<span x-text="item.limit_price"></span>)
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="flex items-center justify-center space-x-1">
                                                        <button type="button" @click="item.quantity > 1 ? item.quantity-- : null" class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300">-</button>
                                                        <input type="number" x-model="item.quantity" class="w-14 text-center bg-gray-50 dark:bg-gray-700 border-0 text-sm font-semibold text-gray-900 dark:text-white focus:ring-0 rounded-md p-1" min="1">
                                                        <button type="button" @click="item.quantity < item.max_stock ? item.quantity++ : null" class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300">+</button>
                                                    </div>
                                                    <div x-show="item.quantity > item.max_stock" class="text-xs text-red-500 mt-1">Max: <span x-text="item.max_stock"></span></div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" x-model="item.selling_price" class="w-24 text-right bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" step="0.01">
                                                    <div x-show="isResellerOrder" class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                        Comm: <span x-text="(item.selling_price - item.limit_price).toFixed(2)"></span>
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

                        <!-- Payment & Information Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Add Payment Details Card -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-bold text-blue-700 dark:text-blue-500 mb-4">Add Payment</h3>
                                
                                <div class="space-y-4">
                                    <!-- Sub Total -->
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Sub Total</label>
                                        <div class="w-full bg-gray-100 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="subTotal.toFixed(2)"></span>
                                        </div>
                                    </div>

                                    <!-- Courier -->
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Courier</label>
                                        <select x-model="form.courier_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="">Select Courier</option>
                                            @foreach($couriers as $courier)
                                                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Delivery Charge -->
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Delivery Charge</label>
                                        <input type="number" x-model="form.courier_charge" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" step="0.01">
                                    </div>

                                    <!-- Commission Agent (Reseller) -->
                                    <div x-show="isResellerOrder">
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Commission Agent</label>
                                        <div class="w-full bg-gray-100 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="totalCommission"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Payment Method -->
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Payment Method</label>
                                        <select x-model="form.payment_method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="Cash on Delivery">Cash on Delivery</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Online Payment">Online Payment</option>
                                        </select>
                                    </div>

                                     <!-- Call Status -->
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Call Status</label>
                                        <select x-model="form.call_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="pending">Pending</option>
                                            <option value="confirm">Confirm</option>
                                            <option value="cancel">Cancel</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Card -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 h-fit">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Summary</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Net Total</label>
                                        <div class="w-full bg-gray-200 border border-gray-300 text-gray-900 text-lg rounded-lg p-3 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="totalAmount"></span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-900 dark:text-white">Balance</label>
                                        <div class="w-full bg-gray-200 border border-gray-300 text-gray-900 text-lg rounded-lg p-3 font-bold dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <span x-text="totalAmount"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center mt-4">
                                         <input type="checkbox" id="send_sms" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                         <label for="send_sms" class="ml-2 text-sm text-gray-900 dark:text-gray-300">Send sms (Confirm orders only)</label>
                                    </div>

                                    <div class="pt-4 mt-4">
                                        <button type="submit" 
                                                class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition duration-200"
                                                :disabled="form.items.length === 0 || isSubmitting">
                                            <span x-show="!isSubmitting">Save Order</span>
                                            <span x-show="isSubmitting">Processing...</span>
                                        </button>
                                        <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-2">Instead of Clicky IT Solution</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                </div>
            </form>
        </div>
    </div>

    <!-- Re-using the same orderManager logic from previous step, but ensuring valid JSON passed -->
    <script>
        function orderManager() {
            return {
                isSubmitting: false,
                isResellerOrder: false,
                resellerSearch: '',
                resellers: [],
                selectedReseller: null,
                
                productSearch: '',
                productResults: [],

                provinces: @json($slData),
                availableDistricts: [],
                
                form: {
                    order_type: 'direct', 
                    order_date: new Date().toISOString().split('T')[0],
                    order_status: 'pending',
                    reseller_id: null,
                    courier_id: null,
                    courier_charge: 0,
                    payment_method: 'Cash on Delivery',
                    call_status: 'pending',
                    sales_note: '',

                    customer: {
                        name: '',
                        mobile: '',
                        landline: '',
                        address: '',
                        city: '',
                        district: '',
                        province: 'Western' 
                    },
                    items: []
                },
                
                init() {
                    this.$watch('isResellerOrder', (val) => {
                         this.form.order_type = val ? 'reseller' : 'direct';
                    });
                    this.$watch('form.customer.province', (value) => {
                        this.updateDistricts();
                    });
                    this.updateDistricts();
                },
                
                updateDistricts() {
                    if (this.provinces) {
                        let all = [];
                        Object.values(this.provinces).forEach(d => all = all.concat(d));
                        this.availableDistricts = all.sort();
                    }
                },
                
                async searchResellers() {
                    if (this.resellerSearch.length < 2) {
                        this.resellers = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/orders/search-resellers?q=${this.resellerSearch}`);
                        this.resellers = await response.json();
                    } catch (error) {
                        console.error('Error searching resellers:', error);
                    }
                },
                
                selectReseller(reseller) {
                    this.selectedReseller = reseller;
                    this.form.reseller_id = reseller.id;
                    this.isResellerOrder = true;
                    this.resellers = [];
                    this.resellerSearch = '';
                },
                
                clearReseller() {
                    this.selectedReseller = null;
                    this.form.reseller_id = null;
                    this.isResellerOrder = false;
                },
                
                async searchProducts() {
                    if (this.productSearch.length < 2) {
                        this.productResults = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/orders/search-products?q=${this.productSearch}`);
                        this.productResults = await response.json();
                    } catch (error) {
                        console.error('Error searching products:', error);
                    }
                },
                
                addItem(product) {
                     if (product.stock <= 0) {
                         alert("This product is out of stock.");
                         return;
                     }
                     const existing = this.form.items.find(i => i.id === product.id);
                     if (existing) {
                         if (existing.quantity < product.stock) {
                             existing.quantity++;
                         } else {
                             alert("Max stock reached for this item.");
                         }
                     } else {
                         this.form.items.push({
                             id: product.id,
                             name: product.name,
                             sku: product.sku,
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
                
                removeItem(index) {
                    this.form.items.splice(index, 1);
                },
                
                get subTotal() {
                    return this.form.items.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
                },

                get totalAmount() {
                    const sub = this.subTotal;
                    const courier = parseFloat(this.form.courier_charge) || 0;
                    return (sub + courier).toFixed(2);
                },
                
                get totalCommission() {
                    if (!this.isResellerOrder) return '0.00';
                    return this.form.items.reduce((sum, item) => {
                        const commissionPerUnit = item.selling_price - item.limit_price;
                        return sum + (item.quantity * commissionPerUnit);
                    }, 0).toFixed(2);
                },
                
                async submitOrder() {
                    if (this.isResellerOrder && !this.form.reseller_id) {
                        alert("Please select a reseller.");
                        return;
                    }
                    const invalidPrice = this.form.items.find(item => item.selling_price < item.limit_price);
                    if (invalidPrice) {
                        alert(`Selling price for ${invalidPrice.name} cannot be lower than the limit price (${invalidPrice.limit_price}).`);
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
                        if (result.success) {
                            window.location.href = result.redirect;
                        } else {
                            alert("Error: " + result.message);
                            this.isSubmitting = false;
                        }
                    } catch (error) {
                        console.error('Submission error:', error);
                        alert("An unexpected error occurred.");
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
