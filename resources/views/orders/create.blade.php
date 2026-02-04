<x-app-layout>
    <div x-data="orderManager()" class="py-12" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Breadcrumb -->
            <nav class="flex mb-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('orders.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Orders</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Create Order</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <form @submit.prevent="submitOrder">
                <!-- Top Controls: Type & Date -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Order Type -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Type</h3>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="order_type" value="direct" x-model="form.order_type" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Direct Order</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="order_type" value="reseller" x-model="form.order_type" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Reseller Order</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Date Picker -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Date</h3>
                        <input type="date" x-model="form.order_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <!-- Order ID Preview -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 flex items-center justify-center">
                        <div class="text-center">
                            <span class="block text-sm text-gray-500 dark:text-gray-400">System Generated ID</span>
                            <span class="block text-2xl font-bold text-gray-900 dark:text-white mt-1">ORD-XXXX</span>
                        </div>
                    </div>
                </div>

                <!-- Reseller Selection (Conditional) -->
                <div x-show="form.order_type === 'reseller'" x-transition class="mb-6">
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-lg shadow-md border border-purple-200 dark:border-purple-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Select Reseller
                        </h3>
                        
                        <div class="relative">
                            <input type="text" 
                                   x-model="resellerSearch" 
                                   @input.debounce.300ms="searchResellers()" 
                                   placeholder="Search Reseller by Name or Mobile..." 
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            
                            <!-- Reseller Dropdown -->
                            <div x-show="resellers.length > 0 && !selectedReseller" @click.outside="resellers = []" class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                                <ul>
                                    <template x-for="reseller in resellers" :key="reseller.id">
                                        <li @click="selectReseller(reseller)" class="px-4 py-2 hover:bg-purple-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-bold" x-text="reseller.name"></div>
                                            <div class="text-xs text-gray-500" x-text="reseller.mobile"></div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <!-- Selected Reseller Display -->
                        <div x-show="selectedReseller" class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-800 flex justify-between items-center">
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white" x-text="selectedReseller?.name"></div>
                                <div class="text-sm text-gray-500" x-text="selectedReseller?.mobile"></div>
                            </div>
                            <button type="button" @click="clearReseller()" class="text-red-500 hover:text-red-700 text-sm font-medium">Change</button>
                        </div>
                        <p x-show="form.order_type === 'reseller' && !form.reseller_id" class="mt-2 text-sm text-red-600 dark:text-red-400">Please select a reseller.</p>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Customer Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mobile Number <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.customer.mobile" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                        <div>
                             <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.customer.name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Landline (Optional)</label>
                            <input type="text" x-model="form.customer.landline" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">City (Optional)</label>
                             <input type="text" x-model="form.customer.city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address <span class="text-red-500">*</span></label>
                            <textarea x-model="form.customer.address" rows="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Product Selection -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        Order Items
                    </h3>

                    <!-- Product Search -->
                    <div class="mb-6 relative">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Search & Add Products</label>
                        <input type="text" 
                               x-model="productSearch" 
                               @input.debounce.300ms="searchProducts()" 
                               placeholder="Type to search product name or SKU..." 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        
                        <!-- Search Results -->
                        <div x-show="productResults.length > 0" @click.outside="productResults = []" class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1 max-h-96 overflow-y-auto dark:bg-gray-700 border border-gray-200 dark:border-gray-600 ring-1 ring-black ring-opacity-5">
                             <ul>
                                <template x-for="product in productResults" :key="product.id">
                                    <li @click="addItem(product)" class="px-4 py-3 hover:bg-green-50 dark:hover:bg-gray-600 cursor-pointer flex items-center border-b dark:border-gray-600 last:border-0">
                                        <div class="flex-shrink-0 h-10 w-10 mr-3">
                                            <img :src="product.image ? '/storage/' + product.image : 'https://ui-avatars.com/api/?name=' + product.name" class="h-10 w-10 rounded-full object-cover bg-gray-100">
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 dark:text-white text-sm" x-text="product.name"></div>
                                            <div class="flex items-center text-xs text-gray-500 mt-1">
                                                <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded dark:bg-gray-600 dark:text-gray-300 mr-2" x-text="product.sku"></span>
                                                <span :class="product.stock > 0 ? 'text-green-600' : 'text-red-500'" x-text="product.stock > 0 ? product.stock + ' in Stock' : 'Out of Stock'"></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">LKR <span x-text="product.selling_price"></span></div>
                                            <div class="text-xs text-red-500">Min: <span x-text="product.limit_price"></span></div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <!-- Cart Table -->
                    <div class="overflow-x-auto border rounded-lg dark:border-gray-700" x-show="form.items.length > 0">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Product</th>
                                    <th scope="col" class="px-6 py-3 text-center">Quantity</th>
                                    <th scope="col" class="px-6 py-3 text-right">Selling Price</th>
                                    <th scope="col" class="px-6 py-3 text-right">Total</th>
                                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in form.items" :key="item.id">
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            <div x-text="item.name"></div>
                                            <div class="text-xs text-gray-500" x-text="item.sku"></div>
                                            <!-- Error Message for Price -->
                                            <div x-show="item.selling_price < item.limit_price" class="text-xs text-red-500 font-bold mt-1">
                                                Below Limit Price (Min: <span x-text="item.limit_price"></span>)
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button type="button" @click="item.quantity > 1 ? item.quantity-- : null" class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                                </button>
                                                <input type="number" x-model="item.quantity" class="w-16 text-center bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" min="1">
                                                <button type="button" @click="item.quantity < item.max_stock ? item.quantity++ : null" class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                                </button>
                                            </div>
                                             <div x-show="item.quantity > item.max_stock" class="text-xs text-red-500 text-center mt-1">Max Stock: <span x-text="item.max_stock"></span></div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <input type="number" x-model="item.selling_price" class="w-24 text-right bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block ml-auto p-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" step="0.01">
                                            
                                            <!-- Commission Preview -->
                                            <div x-show="form.order_type === 'reseller'" class="text-xs text-purple-600 font-medium mt-1">
                                                Comm: <span x-text="(item.selling_price - item.limit_price).toFixed(2)"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                            <span x-text="(item.quantity * item.selling_price).toFixed(2)"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-100 dark:bg-gray-700 font-semibold text-gray-900 dark:text-white">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right">Total Amount:</td>
                                    <td class="px-6 py-3 text-right">
                                        LKR <span x-text="totalAmount"></span>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr x-show="form.order_type === 'reseller'">
                                    <td colspan="3" class="px-6 py-3 text-right text-purple-600">Total Commission:</td>
                                    <td class="px-6 py-3 text-right text-purple-600">
                                        LKR <span x-text="totalCommission"></span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                     <div x-show="form.items.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 rounded-lg">
                        Search and add products to start order.
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white text-sm font-medium">Cancel</a>
                    <button type="submit" 
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="form.items.length === 0 || isSubmitting">
                        <span x-show="!isSubmitting">Create Order</span>
                        <span x-show="isSubmitting">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function orderManager() {
            return {
                isSubmitting: false,
                resellerSearch: '',
                resellers: [],
                selectedReseller: null,
                
                productSearch: '',
                productResults: [],
                
                form: {
                    order_type: 'direct', // Default
                    order_date: new Date().toISOString().split('T')[0],
                    reseller_id: null,
                    customer: {
                        name: '',
                        mobile: '',
                        landline: '',
                        address: '',
                        city: ''
                    },
                    items: []
                },
                
                init() {
                    // Watchers if needed
                },
                
                // --- Search Logic ---
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
                    this.resellers = [];
                    this.resellerSearch = '';
                },
                
                clearReseller() {
                    this.selectedReseller = null;
                    this.form.reseller_id = null;
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
                
                // --- Cart Logic ---
                addItem(product) {
                     if (product.stock <= 0) {
                         alert("This product is out of stock.");
                         return;
                     }
                     
                     // Check if already exists
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
                             selling_price: parseFloat(product.selling_price), // Default to current SP
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
                
                // --- Computed Totals ---
                get totalAmount() {
                    return this.form.items.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0).toFixed(2);
                },
                
                get totalCommission() {
                    if (this.form.order_type !== 'reseller') return '0.00';
                    return this.form.items.reduce((sum, item) => {
                        const commissionPerUnit = item.selling_price - item.limit_price;
                        return sum + (item.quantity * commissionPerUnit);
                    }, 0).toFixed(2);
                },
                
                // --- Submission ---
                async submitOrder() {
                    // Validation
                    if (this.form.order_type === 'reseller' && !this.form.reseller_id) {
                        alert("Please select a reseller.");
                        return;
                    }
                    
                    // Price Validation
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
                            // Redirect
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
