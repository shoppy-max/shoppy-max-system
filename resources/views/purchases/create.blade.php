<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Purchase Order') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6" x-data="purchaseForm()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-8">
                    
                    <!-- Form Start -->
                    <form action="{{ route('purchases.store') }}" method="POST">
                        @csrf
                        
                        <!-- Supplier Section -->
                        <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                Supplier Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="supplier_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Supplier <span class="text-red-500">*</span></label>
                                    <select name="supplier_id" id="supplier_id" required class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Choose Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->business_name }} ({{ $supplier->name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                Order Items
                            </h3>
                            
                            <div class="space-y-4">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm transition-all hover:shadow-md">
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                            
                                            <!-- Product Selection / Entry -->
                                            <div class="md:col-span-6 lg:col-span-5">
                                                <div class="flex justify-between mb-1">
                                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Product</label>
                                                    <button type="button" @click="item.is_new = !item.is_new; item.product_id = ''; item.product_name = ''" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                                        <span x-text="item.is_new ? 'Select Existing' : 'Add New Product'"></span>
                                                    </button>
                                                </div>

                                                <!-- Select Existing -->
                                                <div x-show="!item.is_new">
                                                    <select :name="'items['+index+'][product_id]'" x-model="item.product_id" :required="!item.is_new" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                        <option value="">Select Product</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Enter New -->
                                                <div x-show="item.is_new">
                                                    <input type="text" :name="'items['+index+'][product_name]'" x-model="item.product_name" placeholder="Enter New Product Name" :required="item.is_new" class="bg-gray-50 border border-blue-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-blue-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ring-2 ring-blue-100 dark:ring-blue-900">
                                                    <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">A draft product will be created.</p>
                                                </div>
                                            </div>

                                            <!-- Quantity -->
                                            <div class="md:col-span-2 lg:col-span-2">
                                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                                                <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" min="1" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            </div>

                                            <!-- Price -->
                                            <div class="md:col-span-3 lg:col-span-3">
                                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Purchasing Price</label>
                                                <input type="number" :name="'items['+index+'][purchasing_price]'" x-model="item.price" min="0" step="0.01" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            </div>

                                            <!-- Total & Delete -->
                                            <div class="md:col-span-2 flex items-center justify-between md:justify-end gap-3">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-white md:hidden">
                                                    Subtotal: Rs. <span x-text="(item.quantity * item.price).toFixed(2)"></span>
                                                </div>
                                                <button type="button" @click="removeItem(index)" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900 transition-colors" x-show="items.length > 1" title="Remove Item">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <button type="button" @click="addItem()" class="w-full md:w-auto text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 focus:ring-4 focus:ring-blue-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800 dark:hover:bg-blue-900/50 flex items-center justify-center transition-all">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Another Item
                                </button>
                            </div>
                        </div>
                        
                        <!-- Footer Actions -->
                        <div class="flex flex-col md:flex-row items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="mb-4 md:mb-0">
                                <span class="text-lg font-medium text-gray-500 dark:text-gray-400">Total Estimated Cost:</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white ml-2">Rs. <span x-text="grandTotal()"></span></span>
                            </div>

                            <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                             <input type="hidden" name="auto_verify" id="auto_verify_input" value="0">
                             
                            <a href="{{ route('purchases.index') }}" class="text-gray-900 hover:text-white border border-gray-800 hover:bg-gray-900 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2 dark:border-gray-600 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 transition-colors">
                                Cancel
                            </a>
                            
                            <button type="submit" onclick="document.getElementById('auto_verify_input').value='0'" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 shadow-md">
                                Save Draft
                            </button>

                            <button type="submit" onclick="document.getElementById('auto_verify_input').value='1'" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none dark:focus:ring-green-800 shadow-md">
                                Save & Verify (Add Stock)
                            </button>
                        </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function purchaseForm() {
            return {
                items: [{ product_id: '', product_name: '', quantity: 1, price: 0, is_new: false }],
                addItem() {
                    this.items.push({ product_id: '', product_name: '', quantity: 1, price: 0, is_new: false });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                grandTotal() {
                    return this.items.reduce((total, item) => total + (item.price * item.quantity), 0).toFixed(2);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
