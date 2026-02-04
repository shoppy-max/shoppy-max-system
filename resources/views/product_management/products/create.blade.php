<x-app-layout>
    <x-form-layout>
        <div class="mb-6">
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
                            <a href="{{ route('products.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Products</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Add Product</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Add New Product</h2>
        </div>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" 
              x-data="productForm({{ json_encode($units) }}, {{ json_encode(old('variants', [['unit_id' => '', 'unit_value' => '', 'sku' => '', 'selling_price' => '', 'limit_price' => '', 'quantity' => 0, 'alert_quantity' => 5]])) }})">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (Main Info) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Basic Information -->
                    <x-form-section title="Basic Information" description="Enter the core product details.">
                        <div class="space-y-6">
                            <div>
                                <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Product Name <span class="text-red-500">*</span></label>
                                <input type="text" id="name" name="name" x-model="name" value="{{ old('name') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="e.g. Wireless Headphones" required>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="category_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category <span class="text-red-500">*</span></label>
                                    <div class="flex gap-2">
                                        <select id="category_id" name="category_id" x-model="selectedCategory" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                            <template x-for="cat in newCategories" :key="cat.id">
                                                <option :value="cat.id" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="openModal('category')" class="px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <label for="sub_category_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sub Category</label>
                                    <div class="flex gap-2">
                                        <select id="sub_category_id" name="sub_category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            <option value="">Select Sub Category</option>
                                            @foreach($subCategories as $subCategory)
                                                <option value="{{ $subCategory->id }}" data-category="{{ $subCategory->category_id }}" x-show="selectedCategory == {{ $subCategory->category_id }} || selectedCategory == ''">{{ $subCategory->name }}</option>
                                            @endforeach
                                             <template x-for="sub in newSubCategories" :key="sub.id">
                                                <option :value="sub.id" x-text="sub.name" x-show="selectedCategory == sub.category_id"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="openModal('subCategory')" class="px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" :disabled="!selectedCategory" :class="{'opacity-50 cursor-not-allowed': !selectedCategory}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Description</label>
                                <textarea id="description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Product description...">{{ old('description') }}</textarea>
                            </div>

                            <!-- Warranty Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="warranty_period" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Warranty Period</label>
                                    <input type="number" id="warranty_period" name="warranty_period" value="{{ old('warranty_period') }}" min="0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="e.g., 12">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter warranty duration number</p>
                                </div>
                                
                                <div>
                                    <label for="warranty_period_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Warranty Period Type</label>
                                    <select id="warranty_period_type" name="warranty_period_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Select Type</option>
                                        <option value="years" {{ old('warranty_period_type') == 'years' ? 'selected' : '' }}>Years</option>
                                        <option value="months" {{ old('warranty_period_type') == 'months' ? 'selected' : '' }}>Months</option>
                                        <option value="days" {{ old('warranty_period_type') == 'days' ? 'selected' : '' }}>Days</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select warranty time unit</p>
                                </div>
                            </div>
                        </div>
                    </x-form-section>

                    <!-- Product Variants -->
                    <x-form-section title="Product Variants" description="Manage different units/variants (e.g., 1kg, 500g) with specific pricing and stock.">
                        <div class="space-y-6">
                            
                            <template x-for="(variant, index) in variants" :key="index">
                                <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-800 relative">
                                    
                                    <!-- Remove Button -->
                                    <button type="button" @click="removeVariant(index)" x-show="variants.length > 1" class="absolute top-2 right-2 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <!-- Unit -->
                                        <div>
                                            <label :for="'variant_unit_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Unit <span class="text-red-500">*</span></label>
                                            <select :id="'variant_unit_'+index" :name="'variants['+index+'][unit_id]'" x-model="variant.unit_id" @change="generateSmartSku(index)" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                                <option value="">Select Unit</option>
                                                @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                                @endforeach
                                                <template x-for="unit in newUnits" :key="unit.id">
                                                    <option :value="unit.id" x-text="unit.name + ' (' + unit.short_name + ')'"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <!-- Unit Value -->
                                        <div>
                                            <label :for="'variant_unit_value_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Value (Optional)</label>
                                            <input type="text" :id="'variant_unit_value_'+index" :name="'variants['+index+'][unit_value]'" x-model="variant.unit_value" @input="generateSmartSku(index)" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="e.g. 500">
                                        </div>

                                        <!-- SKU -->
                                        <div>
                                            <label :for="'variant_sku_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SKU <span class="text-red-500">*</span></label>
                                            <div class="flex">
                                                 <span @click="generateSmartSku(index)" class="cursor-pointer inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-md dark:bg-gray-600 dark:text-gray-400 dark:border-gray-600 hover:bg-gray-300" title="Click to Generate">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </span>
                                                <input type="text" :id="'variant_sku_'+index" :name="'variants['+index+'][sku]'" x-model="variant.sku" class="rounded-none rounded-r-lg bg-white border border-gray-300 text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                            </div>
                                        </div>

                                        <!-- Selling Price -->
                                        <div>
                                            <label :for="'variant_price_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Price <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                 <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <span class="text-gray-500 dark:text-gray-400">Rs.</span>
                                                </div>
                                                <input type="number" step="0.01" :id="'variant_price_'+index" :name="'variants['+index+'][selling_price]'" x-model="variant.selling_price" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="0.00" required>
                                            </div>
                                        </div>

                                        <!-- Limit Price -->
                                        <div>
                                            <label :for="'variant_limit_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Limit Price</label>
                                            <input type="number" step="0.01" :id="'variant_limit_'+index" :name="'variants['+index+'][limit_price]'" x-model="variant.limit_price" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="0.00">
                                        </div>

                                        <!-- Quantity -->
                                        <div>
                                            <label :for="'variant_qty_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Quantity <span class="text-red-500">*</span></label>
                                            <input type="number" :id="'variant_qty_'+index" :name="'variants['+index+'][quantity]'" x-model="variant.quantity" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                        </div>

                                         <!-- Variant Image -->
                                        <div>
                                            <label :for="'variant_image_'+index" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Image (Optional)</label>
                                            <input type="file" :id="'variant_image_'+index" :name="'variants['+index+'][image]'" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="addVariant" class="text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2 dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:hover:bg-blue-500 dark:focus:ring-blue-800 w-full flex justify-center items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add Another Variant
                            </button>

                             <x-input-error :messages="$errors->get('variants')" class="mt-2" />
                        </div>
                    </x-form-section>
                </div>

                <!-- Right Column (Media & Actions) -->
                <div class="space-y-6">
                    <x-form-section title="Product Media">
                        <!-- Image Upload with Preview -->
                        <div x-data="imageViewer()">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="image">Main Product Image</label>
                            
                            <div class="flex items-center justify-center w-full">
                                <label for="image" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 relative overflow-hidden group">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6" x-show="!imageUrl">
                                        <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span></p>
                                    </div>
                                    <img x-show="imageUrl" :src="imageUrl" class="absolute inset-0 w-full h-full object-cover" />
                                    <input id="image" name="image" type="file" class="hidden" accept="image/*" @change="fileChosen">
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                        </div>
                    </x-form-section>

                    <!-- Actions -->
                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 shadow-lg transition-transform active:scale-95">
                            Save Product
                        </button>
                        <a href="{{ route('products.index') }}" class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 text-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
            
             <!-- Quick Add Modals -->
             @include('product_management.products.partials.quick-add-modals')

        </form>
    </x-form-layout>

    <script>
        function productForm(backendUnits, oldVariants) {
            return {
                showCategoryModal: false,
                showSubCategoryModal: false,
                showUnitModal: false,
                selectedCategory: '{{ old('category_id') }}',
                newCategories: [],
                newSubCategories: [],
                newUnits: [],
                unitsList: backendUnits, // To access names if needed, though simple select is enough
                
                // Variants Data
                variants: oldVariants,

                // Form Data (Main)
                name: '{{ old('name') }}',

                // Form Data for Modals
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
                },

                addVariant() {
                    this.variants.push({
                         unit_id: '',
                         unit_value: '',
                         sku: '',
                         selling_price: '',
                         limit_price: '',
                         quantity: 0,
                         alert_quantity: 5
                    });
                    this.generateSmartSku(this.variants.length - 1);
                },

                removeVariant(index) {
                    this.variants.splice(index, 1);
                },

                generateSmartSku(index) {
                     let variant = this.variants[index];
                     
                     // 1. Product Name Code (3 chars, Upper)
                     let namePart = this.name ? this.name.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, 'X') : 'PRO';
                     if(namePart.length < 3) namePart = namePart.padEnd(3, 'X');
                     
                     // 2. Variant Spec Code (Value + Unit)
                     let unitObj = this.unitsList.find(u => u.id == variant.unit_id);
                     let specPart = 'VAR';
                     if (unitObj) {
                         let val = variant.unit_value ? variant.unit_value.replace(/[^0-9a-zA-Z]/g, '') : '';
                         specPart = val + unitObj.short_name.toUpperCase();
                     } else {
                        // Fallback/Random if no unit selected
                        specPart = 'GEN';
                     }
                     
                     // 3. Random Suffix (4 chars) - Try to preserve existing if present to avoid flickering
                     let currentSku = variant.sku || '';
                     let randomSuffix = '';
                     let matches = currentSku.match(/-([A-Z0-9]{4})$/);
                     if (matches) {
                         randomSuffix = matches[1];
                     } else {
                         randomSuffix = Math.random().toString(36).substring(2, 6).toUpperCase();
                     }
                     
                     // Combine: SK + Name + - + Spec + - + Random
                     this.variants[index].sku = `SK${namePart}-${specPart}-${randomSuffix}`;
                },
                
                updateAllSkus() {
                    this.variants.forEach((_, index) => this.generateSmartSku(index));
                },
                
                init() {
                     this.$watch('name', () => this.updateAllSkus());
                     
                     if (this.variants.length === 1 && !this.variants[0].sku) {
                         this.generateSmartSku(0);
                     }
                },

                // API Calls (same as before)
                async saveCategory() {
                    try {
                        let response = await axios.post('{{ route("quick.category.store") }}', this.newCategoryForm);
                        if(response.data.success) {
                            this.newCategories.push(response.data.category);
                            this.selectedCategory = response.data.category.id;
                            this.showCategoryModal = false;
                            this.newCategoryForm = { name: '', code: '' };
                        }
                    } catch (error) { this.handleQuickAddError(error); }
                },

                async saveSubCategory() {
                    try {
                        let response = await axios.post('{{ route("quick.subcategory.store") }}', this.newSubCategoryForm);
                        if(response.data.success) {
                            this.newSubCategories.push(response.data.subCategory);
                            this.showSubCategoryModal = false;
                            this.newSubCategoryForm = { category_id: '', name: '' };
                        }
                    } catch (error) { this.handleQuickAddError(error); }
                },

                async saveUnit() {
                     try {
                        let response = await axios.post('{{ route("quick.unit.store") }}', this.newUnitForm);
                        if(response.data.success) {
                            this.newUnits.push(response.data.unit);
                            this.showUnitModal = false;
                            this.newUnitForm = { name: '', short_name: '' };
                        }
                    } catch (error) { this.handleQuickAddError(error); }
                },

                handleQuickAddError(error) {
                    let msg = 'Error saving.';
                    if (error.response && error.response.data && error.response.data.errors) {
                         msg = Object.values(error.response.data.errors).flat().join('\n');
                    }
                    alert(msg);
                }
            }
        }
        
        function imageViewer() {
            return {
                imageUrl: '',
                fileChosen(event) {
                    this.fileToDataUrl(event, src => this.imageUrl = src)
                },
                fileToDataUrl(event, callback) {
                    if (! event.target.files.length) return
                    let file = event.target.files[0],
                        reader = new FileReader()
                    reader.readAsDataURL(file)
                    reader.onload = e => callback(e.target.result)
                }
            }
        }
    </script>
</x-app-layout>
