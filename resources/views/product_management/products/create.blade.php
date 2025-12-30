<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Product') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6" x-data="productForm()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-8">
                    
                    <!-- Error Message Display -->
                    <template x-if="errorMessage">
                        <div class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200 rounded-lg dark:bg-red-900/30 dark:text-red-300 dark:border-red-800" role="alert">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                <span class="font-medium" x-text="errorMessage"></span>
                            </div>
                        </div>
                    </template>

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200 rounded-lg dark:bg-red-900/30 dark:text-red-300 dark:border-red-800" role="alert">
                            <ul class="list-disc list-inside text-sm ml-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            
                            <!-- Basic Info Section -->
                            <div class="space-y-6">
                                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Basic Information
                                    </h3>
                                </div>
                                
                                <div>
                                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Product Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="e.g. Product Name" required>
                                </div>

                                <div>
                                    <label for="sku" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SKU / Barcode <span class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <span class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-md dark:bg-gray-600 dark:text-gray-400 dark:border-gray-600">
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </span>
                                        <input type="text" id="sku" name="sku" value="{{ old('sku', 'SKU-' . strtoupper(Str::random(8))) }}" class="rounded-none rounded-r-lg bg-gray-50 border border-gray-300 text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Barcode will be generated automatically from this SKU.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <label for="category_id" class="block text-sm font-medium text-gray-900 dark:text-white">Category <span class="text-red-500">*</span></label>
                                            <button type="button" @click="openModal('category')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add New
                                            </button>
                                        </div>
                                        <select id="category_id" name="category_id" x-model="selectedCategory" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                            <template x-for="cat in newCategories" :key="cat.id">
                                                <option :value="cat.id" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <label for="sub_category_id" class="block text-sm font-medium text-gray-900 dark:text-white">Sub Category</label>
                                            <button type="button" @click="openModal('subCategory')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add New
                                            </button>
                                        </div>
                                        <select id="sub_category_id" name="sub_category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Sub Category</option>
                                             @foreach($subCategories as $subCategory)
                                                <option value="{{ $subCategory->id }}">{{ $subCategory->name }} ({{ $subCategory->category->name ?? 'No Parent' }})</option>
                                            @endforeach
                                            <template x-for="sub in newSubCategories" :key="sub.id">
                                                <option :value="sub.id" x-text="sub.name + ' (New)'"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label for="unit_id" class="block text-sm font-medium text-gray-900 dark:text-white">Unit</label>
                                        <button type="button" @click="openModal('unit')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Add New
                                        </button>
                                    </div>
                                     <select id="unit_id" name="unit_id" x-model="selectedUnit" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                        @endforeach
                                        <template x-for="u in newUnits" :key="u.id">
                                            <option :value="u.id" x-text="u.name + ' (' + u.short_name + ')'"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Description</label>
                                    <textarea id="description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Product description...">{{ old('description') }}</textarea>
                                </div>
                                
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="image">Product Image</label>
                                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="image" name="image" type="file" accept="image/*">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="file_input_help">SVG, PNG, JPG or GIF (MAX. 2MB).</p>
                                </div>
                            </div>

                            <!-- Pricing & Inventory Section -->
                            <div class="space-y-6">
                                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Pricing & Inventory
                                    </h3>
                                </div>
                                
                                <div>
                                    <label for="selling_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Selling Price <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400">Rs.</span>
                                        </div>
                                        <input type="number" step="0.01" id="selling_price" name="selling_price" value="{{ old('selling_price') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="0.00" required>
                                    </div>
                                </div>

                                <div>
                                    <label for="limit_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Limit Price (Min)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400">Rs.</span>
                                        </div>
                                        <input type="number" step="0.01" id="limit_price" name="limit_price" value="{{ old('limit_price') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="0.00">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The minimum price at which this product can be sold.</p>
                                </div>

                                <div>
                                    <label for="alert_quantity" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alert Quantity</label>
                                    <input type="number" id="alert_quantity" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Get notified when stock drops below this level.</p>
                                </div>

                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Current Stock</label>
                                    <div class="relative">
                                        <input type="text" value="0" disabled class="bg-gray-200 border border-gray-300 text-gray-500 text-sm rounded-lg cursor-not-allowed block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-400">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Initial stock is 0. Please create a purchase entry to add stock.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('products.index') }}" class="text-gray-900 hover:text-white border border-gray-800 hover:bg-gray-900 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2 dark:border-gray-600 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 shadow-md transition-transform hover:scale-105">
                                Save Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Category Modal -->
        <div x-show="showCategoryModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showCategoryModal" @click="showCategoryModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                             <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Add New Category</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                        <input type="text" x-model="newCategoryForm.name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                                        <input type="text" x-model="newCategoryForm.code" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="saveCategory()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                        <button type="button" @click="showCategoryModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 hover:dark:bg-gray-500">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub Modal -->
        <div x-show="showSubCategoryModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showSubCategoryModal" @click="showSubCategoryModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                         <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Add New Sub Category</h3>
                                <div class="mt-4 space-y-4">
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parent Category</label>
                                        <!-- If category is already selected in main form, pre-select it here or lock it -->
                                        <select x-model="newSubCategoryForm.category_id" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                            <template x-for="cat in newCategories" :key="cat.id">
                                                <option :value="cat.id" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                        <input type="text" x-model="newSubCategoryForm.name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>
                        </div>
                    </div>
                     <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="saveSubCategory()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                        <button type="button" @click="showSubCategoryModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 hover:dark:bg-gray-500">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unit Modal -->
        <div x-show="showUnitModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showUnitModal" @click="showUnitModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Add New Unit</h3>
                                <div class="mt-4 space-y-4">
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                        <input type="text" x-model="newUnitForm.name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Short Name</label>
                                        <input type="text" x-model="newUnitForm.short_name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>
                        </div>
                    </div>
                     <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="saveUnit()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                        <button type="button" @click="showUnitModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 hover:dark:bg-gray-500">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function productForm() {
            return {
                showCategoryModal: false,
                showSubCategoryModal: false,
                showUnitModal: false,
                selectedCategory: '',
                selectedUnit: '',
                newCategories: [],
                newSubCategories: [],
                newUnits: [],
                errorMessage: '',
                
                newCategoryForm: { name: '', code: '' },
                newSubCategoryForm: { category_id: '', name: '' },
                newUnitForm: { name: '', short_name: '' },

                openModal(type) {
                    if(type === 'category') this.showCategoryModal = true;
                    if(type === 'subCategory') {
                         this.newSubCategoryForm.category_id = this.selectedCategory;
                         this.showSubCategoryModal = true;
                    }
                    if(type === 'unit') this.showUnitModal = true;
                    this.errorMessage = '';
                },

                async saveCategory() {
                    try {
                        let response = await axios.post('{{ route("quick.category.store") }}', this.newCategoryForm);
                        if(response.data.success) {
                            this.newCategories.push(response.data.category);
                            this.selectedCategory = response.data.category.id;
                            this.showCategoryModal = false;
                            this.newCategoryForm = { name: '', code: '' };
                        }
                    } catch (error) {
                        this.handleError(error);
                    }
                },

                async saveSubCategory() {
                    try {
                        let response = await axios.post('{{ route("quick.subcategory.store") }}', this.newSubCategoryForm);
                        if(response.data.success) {
                            this.newSubCategories.push(response.data.subCategory);
                             // Auto select modal close
                            this.showSubCategoryModal = false;
                            this.newSubCategoryForm = { category_id: '', name: '' };
                            // Note: We don't verify if it belongs to current Category as that requires complex logic, 
                            // but simpler is to just add it and let user select it if valid.
                        }
                    } catch (error) {
                        this.handleError(error);
                    }
                },

                async saveUnit() {
                     try {
                        let response = await axios.post('{{ route("quick.unit.store") }}', this.newUnitForm);
                        if(response.data.success) {
                            this.newUnits.push(response.data.unit);
                            this.selectedUnit = response.data.unit.id;
                            this.showUnitModal = false;
                            this.newUnitForm = { name: '', short_name: '' };
                        }
                    } catch (error) {
                        this.handleError(error);
                    }
                },

                handleError(error) {
                    if (error.response && error.response.data && error.response.data.errors) {
                         this.errorMessage = Object.values(error.response.data.errors).flat().join(', ');
                    } else {
                        this.errorMessage = 'An unexpected error occurred.';
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
