<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Product Management') }}
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
                    <li aria-current="page">
                         <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Products</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800" x-data="productManager()">
        
        <!-- Advanced Header & Search -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="space-y-4">
                <form method="GET" action="{{ route('products.index') }}" class="space-y-3">
                    <!-- Row 1: Core Filters -->
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                            <!-- Search -->
                            <div class="relative xl:col-span-4">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 1 1 14 0Z"/>
                                    </svg>
                                </div>
                                <input type="search" name="search" value="{{ request('search') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search products, SKU, barcode...">
                            </div>

                            <!-- Category -->
                            <div class="xl:col-span-2" x-data="searchableFilterSelect({
                                name: 'category_id',
                                placeholder: 'All Categories',
                                selected: @js((string) request('category_id', '')),
                                options: @js($categories->map(fn($category) => ['id' => (string) $category->id, 'text' => $category->name])->values()),
                                changeEvent: 'products-category-filter-changed',
                                listenEvent: 'products-category-set-from-subcategory',
                            })">
                                <input type="hidden" :name="name" :value="selected">
                                <div class="relative" @click.away="open = false">
                                    <button type="button" @click="toggle()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white flex items-center justify-between">
                                        <span class="truncate text-left" x-text="selectedText() || placeholder"></span>
                                        <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open" class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-auto" style="display: none;">
                                        <div class="p-2 sticky top-0 bg-white dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                                            <input x-ref="searchInput" type="text" x-model="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Search category...">
                                        </div>
                                        <button type="button" @click="clearSelection()" x-show="selected !== ''" class="w-full text-left px-4 py-2 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-600">Clear selection</button>
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <button type="button" @click="select(option)" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                                <span x-text="option.text"></span>
                                            </button>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No results found</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub Category -->
                            <div class="xl:col-span-3" x-data="searchableFilterSelect({
                                name: 'sub_category_id',
                                placeholder: 'All Sub Categories',
                                selected: @js((string) request('sub_category_id', '')),
                                options: @js($subCategories->map(fn($subCategory) => ['id' => (string) $subCategory->id, 'text' => $subCategory->name, 'category_id' => (string) $subCategory->category_id])->values()),
                                dependentCategoryEvent: 'products-category-filter-changed',
                                selectedCategoryId: @js((string) request('category_id', '')),
                                parentCategoryEvent: 'products-category-set-from-subcategory',
                            })">
                                <input type="hidden" :name="name" :value="selected">
                                <div class="relative" @click.away="open = false">
                                    <button type="button" @click="toggle()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white flex items-center justify-between">
                                        <span class="truncate text-left" x-text="selectedText() || placeholder"></span>
                                        <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open" class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-auto" style="display: none;">
                                        <div class="p-2 sticky top-0 bg-white dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                                            <input x-ref="searchInput" type="text" x-model="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Search sub category...">
                                        </div>
                                        <button type="button" @click="clearSelection()" x-show="selected !== ''" class="w-full text-left px-4 py-2 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-600">Clear selection</button>
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <button type="button" @click="select(option)" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                                <span x-text="option.text"></span>
                                            </button>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No results found</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Unit + Value (In-Stock Only) -->
                            <div class="xl:col-span-3" x-data="searchableFilterSelect({
                                name: 'variant_unit',
                                placeholder: 'All Units / Values',
                                selected: @js((string) request('variant_unit', '')),
                                options: @js($variantUnitOptions),
                            })">
                                <input type="hidden" :name="name" :value="selected">
                                <div class="relative" @click.away="open = false">
                                    <button type="button" @click="toggle()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white flex items-center justify-between">
                                        <span class="truncate text-left" x-text="selectedText() || placeholder"></span>
                                        <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open" class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-64 overflow-auto" style="display: none;">
                                        <div class="p-2 sticky top-0 bg-white dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                                            <input x-ref="searchInput" type="text" x-model="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Search value/unit (e.g. 5 ml)...">
                                        </div>
                                        <button type="button" @click="clearSelection()" x-show="selected !== ''" class="w-full text-left px-4 py-2 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-600">Clear selection</button>
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <button type="button" @click="select(option)" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                                <span x-text="option.text"></span>
                                            </button>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No results found</div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- Row 2: Apply / Clear -->
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:items-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Filter order: category, sub category, then value + unit.
                        </p>
                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            <button type="submit" class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-colors">
                                Apply Filters
                            </button>

                            @if(request('search') || request('category_id') || request('sub_category_id') || request('variant_unit') || request('unit_id'))
                                <a href="{{ route('products.index') }}" class="flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- Page Actions -->
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <a href="{{ route('products.import.show') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            <svg class="w-3 h-3 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Import
                        </a>
                    </div>

                    <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center px-5 py-2 text-sm font-medium text-white transition-colors bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        New Product
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="flex items-center p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-300 dark:border-green-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Success</span>
                <div>
                    <span class="font-medium">Success!</span> {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="relative overflow-x-auto sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                         <th scope="col" class="p-4">
                            <div class="flex items-center">
                                <input id="checkbox-all" type="checkbox" @click="toggleAll()" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="checkbox-all" class="sr-only">checkbox</label>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3">Product Info</th>
                        <th scope="col" class="px-6 py-3">Details</th>
                        <th scope="col" class="px-6 py-3 text-right">Price</th>
                        <th scope="col" class="px-6 py-3 text-right">Limit Price</th>
                        <th scope="col" class="px-6 py-3 text-right">Stock</th>
                        <th scope="col" class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="w-4 p-4">
                                <div class="flex items-center">
                                    <input id="checkbox-{{ $product->id }}" value="{{ $product->id }}" type="checkbox" x-model="selected" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="checkbox-{{ $product->id }}" class="sr-only">checkbox</label>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10">
                                        @if($product->image)
                                            <img class="w-10 h-10 rounded-full object-cover" src="{{ $product->image }}" alt="{{ $product->name }}">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $product->name }}</span>
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                                            @foreach($product->variants->take(3) as $variant)
                                                <span class="text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-600">{{ $variant->sku }}</span>
                                            @endforeach
                                            @if($product->variants->count() > 3)
                                                <span class="text-[10px] text-gray-500">+{{ $product->variants->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300 border border-blue-400 w-fit mb-1">
                                        {{ $product->category->name ?? 'Uncategorized' }}
                                    </span>
                                    @if($product->subCategory)
                                        <span class="text-xs text-gray-600 dark:text-gray-300 mb-1">
                                            Sub Category: {{ $product->subCategory->name }}
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-500">
                                        Units: {{ $product->variants->map(function($v) { return ($v->unit_value ? $v->unit_value . ' ' : '') . $v->unit->short_name; })->unique()->join(', ') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                {{ $product->price_display }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                {{ $product->limit_price_display }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @php
                                    $status = $product->stock_status;
                                    $colorClass = match($status) {
                                        'Out of Stock' => 'text-red-600 bg-red-100 border-red-400',
                                        'Low Stock' => 'text-yellow-600 bg-yellow-100 border-yellow-400',
                                        default => 'text-green-600 bg-green-100 border-green-400',
                                    };
                                @endphp
                                <span class="{{ $colorClass }} border px-2.5 py-0.5 rounded text-xs font-medium whitespace-nowrap">
                                    {{ $product->total_quantity }} ({{ $status }})
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                     <button @click="viewProduct({{ $product->id }})" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg dark:text-blue-400 dark:hover:bg-gray-700 transition-colors" title="View & Print Barcode">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <a href="{{ route('products.edit', $product) }}" class="p-2 text-yellow-600 hover:bg-yellow-100 rounded-lg dark:text-yellow-400 dark:hover:bg-gray-700 transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg dark:text-red-400 dark:hover:bg-gray-700 transition-colors" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    <p class="text-lg font-medium">No products found</p>
                                    <p class="text-sm">Get started by adding a new product to your inventory.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('products.create') }}" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                            Add New Product
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>

        <!-- View / Barcode Modal -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" @click="closeModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" x-transition.scale class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <div class="flex justify-between items-start mb-4 border-b pb-2 dark:border-gray-700">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white" id="modal-title">Product Details</h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div x-show="isLoading" class="flex justify-center p-8">
                        <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    </div>

                    <div x-show="!isLoading && activeProduct">
                        <!-- Product Header Info -->
                        <div class="flex gap-4 mb-4">
                            <div class="flex-shrink-0">
                                <template x-if="activeProduct?.image">
                                    <img :src="activeProduct.image" class="w-20 h-20 rounded-lg object-cover bg-gray-100" />
                                </template>
                                <template x-if="!activeProduct?.image">
                                    <div class="w-20 h-20 rounded-lg bg-gray-200 flex items-center justify-center text-gray-400">
                                       <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white" x-text="activeProduct?.name"></h4>
                                <p class="text-sm text-gray-500" x-text="'Category: ' + (activeProduct?.category?.name || 'N/A')"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2" x-text="activeProduct?.description || 'No description provided.'"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2 xl:grid-cols-3">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sub Category</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="activeProduct?.sub_category?.name || 'N/A'"></p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Warranty</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="activeProduct?.warranty_period ? activeProduct.warranty_period + ' ' + (activeProduct.warranty_period_type || '') : 'No warranty'"></p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Stock</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="activeProduct?.total_quantity ?? '0'"></p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Selling Price Range</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="activeProduct?.price_display ? 'Rs. ' + activeProduct.price_display : 'N/A'"></p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Limit Price Range</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="activeProduct?.limit_price_display && activeProduct.limit_price_display !== 'N/A' ? 'Rs. ' + activeProduct.limit_price_display : 'N/A'"></p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Variants</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="activeProduct?.variants?.length ?? 0"></p>
                            </div>
                        </div>

                        <!-- Variants Table -->
                        <h5 class="text-md font-semibold mb-2 text-gray-900 dark:text-white">Product Variants</h5>
                         <div class="relative overflow-x-auto border rounded-lg border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-2">Unit</th>
                                        <th scope="col" class="px-4 py-2">SKU</th>
                                        <th scope="col" class="px-4 py-2 text-right">Price</th>
                                        <th scope="col" class="px-4 py-2 text-right">Limit Price</th>
                                        <th scope="col" class="px-4 py-2 text-right">Stock</th>
                                        <th scope="col" class="px-4 py-2 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="variant in activeProduct?.variants" :key="variant.id">
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 last:border-b-0">
                                            <td class="px-4 py-2" x-text="(variant.unit_value ? variant.unit_value + ' ' : '') + variant.unit.short_name + ' (' + variant.unit.name + ')'"></td>
                                            <td class="px-4 py-2 font-mono text-xs" x-text="variant.sku"></td>
                                            <td class="px-4 py-2 text-right" x-text="'Rs. ' + parseFloat(variant.selling_price).toFixed(2)"></td>
                                            <td class="px-4 py-2 text-right" x-text="variant.limit_price !== null ? 'Rs. ' + parseFloat(variant.limit_price).toFixed(2) : 'N/A'"></td>
                                            <td class="px-4 py-2 text-right">
                                                 <span :class="variant.quantity <= (variant.alert_quantity || 0) ? 'text-red-600 bg-red-100' : 'text-green-600 bg-green-100'" class="px-2 py-0.5 rounded text-xs font-medium" x-text="variant.quantity"></span>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button @click="window.open('/admin/variants/'+variant.id+'/print-barcode', '_blank', 'width=400,height=400')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-xs">
                                                    Print Barcode
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Double Confirm Delete Modal -->
        <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDeleteModal" @click="showDeleteModal = false" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDeleteModal" x-transition.scale class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 dark:bg-gray-800">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Delete Selected Products</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Are you sure you want to delete <span class="font-bold text-red-600" x-text="selected.length"></span> products? This action cannot be undone.
                                </p>
                                <div class="mt-4">
                                    <label for="delete_confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Type <span class="font-mono font-bold text-red-600">DELETE</span> to confirm
                                    </label>
                                    <input type="text" x-model="deleteConfirmation" id="delete_confirm" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 text-sm focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="DELETE">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                            @click="submitBulkDelete()" 
                            :disabled="deleteConfirmation !== 'DELETE'"
                            :class="deleteConfirmation === 'DELETE' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-gray-300 cursor-not-allowed'"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Delete Products
                        </button>
                        <button type="button" @click="showDeleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:border-gray-600">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Sticky Bulk Action Bar Inside Scope -->
    <div 
        x-show="selected.length > 0" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-4 flex items-center justify-between"
        style="display: none;"
    >
        <div class="container mx-auto max-w-7xl flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span x-text="selected.length" class="font-bold text-gray-900 dark:text-white"></span> items selected
                </span>
                <button @click="toggleAll(false)" class="text-xs text-red-500 hover:underline">Clear Selection</button>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="bulkPrint()" class="flex items-center px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-100 font-medium text-sm dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Barcodes
                </button>
                <button @click="bulkExportAll()" class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium text-sm dark:bg-green-500 dark:hover:bg-green-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export All
                </button>
                <button @click="bulkExport()" class="flex items-center px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium text-sm dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Selected (<span x-text="selected.length"></span>)
                </button>
                <button @click="confirmBulkDelete()" class="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium text-sm dark:bg-red-500 dark:hover:bg-red-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete Selected
                </button>
            </div>
        </div>
    </div>

    </div>

</x-app-layout>
<!-- Sticky Bulk Action Bar -->


<script>
    function searchableFilterSelect(config) {
        return {
            open: false,
            search: '',
            selected: config.selected || '',
            options: config.options || [],
            name: config.name,
            placeholder: config.placeholder || 'Select',
            changeEvent: config.changeEvent || null,
            listenEvent: config.listenEvent || null,
            dependentCategoryEvent: config.dependentCategoryEvent || null,
            parentCategoryEvent: config.parentCategoryEvent || null,
            selectedCategoryId: config.selectedCategoryId || '',

            init() {
                if (this.listenEvent) {
                    window.addEventListener(this.listenEvent, (event) => {
                        this.setSelectedFromEvent(event.detail?.categoryId || '');
                    });
                }

                if (this.dependentCategoryEvent) {
                    window.addEventListener(this.dependentCategoryEvent, (event) => {
                        this.setCategory(event.detail?.categoryId || '');
                    });
                    this.setCategory(this.selectedCategoryId);
                }

                if (this.parentCategoryEvent && this.selected) {
                    this.dispatchParentCategoryForSelection();
                }
            },

            get visibleOptions() {
                if (!this.dependentCategoryEvent || !this.selectedCategoryId) {
                    return this.options;
                }

                return this.options.filter((option) => String(option.category_id || '') === String(this.selectedCategoryId));
            },

            get filteredOptions() {
                const term = this.search.trim().toLowerCase();
                if (!term) {
                    return this.visibleOptions;
                }

                return this.visibleOptions.filter((option) => (option.text || '').toLowerCase().includes(term));
            },

            selectedText() {
                const option = this.options.find((item) => String(item.id) === String(this.selected));
                return option ? option.text : '';
            },

            select(option) {
                this.selected = String(option.id);
                this.search = '';
                this.open = false;
                this.dispatchChange();
                this.dispatchParentCategoryForOption(option);
            },

            clearSelection() {
                this.selected = '';
                this.search = '';
                this.open = false;
                this.dispatchChange();
            },

            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }
            },

            setCategory(categoryId) {
                this.selectedCategoryId = String(categoryId || '');

                if (!this.selected) {
                    return;
                }

                const stillVisible = this.visibleOptions.some((option) => String(option.id) === String(this.selected));
                if (!stillVisible) {
                    this.selected = '';
                }
            },

            setSelectedFromEvent(categoryId) {
                const normalizedId = String(categoryId || '');
                if (!normalizedId) {
                    return;
                }

                const exists = this.options.some((option) => String(option.id) === normalizedId);
                if (!exists) {
                    return;
                }

                if (String(this.selected) !== normalizedId) {
                    this.selected = normalizedId;
                }

                this.dispatchChange();
            },

            dispatchParentCategoryForSelection() {
                const selectedOption = this.options.find((item) => String(item.id) === String(this.selected));
                this.dispatchParentCategoryForOption(selectedOption);
            },

            dispatchParentCategoryForOption(option) {
                if (!this.parentCategoryEvent) {
                    return;
                }

                const categoryId = option?.category_id ? String(option.category_id) : '';
                if (!categoryId) {
                    return;
                }

                window.dispatchEvent(new CustomEvent(this.parentCategoryEvent, {
                    detail: { categoryId },
                }));
            },

            dispatchChange() {
                if (!this.changeEvent) {
                    return;
                }

                window.dispatchEvent(new CustomEvent(this.changeEvent, {
                    detail: { categoryId: this.selected },
                }));
            },
        }
    }

    function productManager() {
        return {
            selected: [],
            allSelected: false,
            showModal: false,
            // Bulk Delete State
            showDeleteModal: false,
            deleteConfirmation: '',
            
            activeProduct: null,
            isLoading: false,

            init() {
                // Persist selection logic could go here if needed
            },

            toggleAll(forceState = null) {
                if (forceState !== null) {
                    this.allSelected = forceState;
                } else {
                    this.allSelected = !this.allSelected;
                }
                
                if(this.allSelected) {
                    // This creates a potential issue if PAGINATION IS USED. 
                    // Best practice: Only select visible. If "Select All" is desired, usually it's a server-side concept.
                    // For now, let's select all visible IDs (from PHP)
                    this.selected = [{{ $products->pluck('id')->implode(',') }}];
                } else {
                    this.selected = [];
                }
            },
            
            bulkPrint() {
                if (this.selected.length === 0) return;
                
                const ids = this.selected.join(',');
                const url = "{{ route('products.barcode.bulk') }}?products=" + ids;
                window.open(url, '_blank');
            },

            bulkExport() {
                if (this.selected.length === 0) return;
                const ids = this.selected.join(',');
                // Redirect to export route with product_ids
                window.location.href = "{{ route('products.export') }}?product_ids=" + ids;
            },

            bulkExportAll() {
                // Export all, preserving current search/filter if applied (optional, or just plain export)
                // Using current URL params would be best:
                const urlParams = new URLSearchParams(window.location.search);
                window.location.href = "{{ route('products.export') }}?" + urlParams.toString();
            },

            confirmBulkDelete() {
                if (this.selected.length === 0) return;
                this.deleteConfirmation = '';
                this.showDeleteModal = true;
            },

            submitBulkDelete() {
                if (this.selected.length === 0 || this.deleteConfirmation !== 'DELETE') return;
                
                // Create a form and submit it to the bulk destroy route
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('products.destroy.bulk') }}";
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
                
                const productsInput = document.createElement('input');
                productsInput.type = 'hidden';
                productsInput.name = 'products';
                productsInput.value = this.selected.join(',');
                form.appendChild(productsInput);
                
                document.body.appendChild(form);
                form.submit();
            }
            
            // ... (rest of the methods: viewProduct etc are kept)
            ,
            async viewProduct(id) {
                this.showModal = true;
                this.activeProduct = null;
                this.isLoading = true;
                try {
                    let response = await axios.get('/admin/products/' + id);
                    this.activeProduct = response.data;
                } catch (error) {
                    console.error(error);
                    alert('Failed to load product details.');
                    this.showModal = false;
                } finally {
                    this.isLoading = false;
                }
            },

            closeModal() {
                this.showModal = false;
                this.activeProduct = null;
            }
        }
    }
</script>
