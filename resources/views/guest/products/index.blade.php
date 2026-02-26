<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Shop - {{ config('app.name', 'ShoppyMax') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50 dark:bg-gray-900">
        
        <!-- Navigation Header -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <a href="{{ route('guest.products') }}" class="flex items-center space-x-3 group">
                        <div class="bg-primary-600 p-2 rounded-xl shadow-lg shadow-primary-500/30 group-hover:shadow-primary-500/50 group-hover:scale-105 transition-all duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-800 dark:text-white tracking-tight">Shoppy<span class="text-primary-600 dark:text-primary-400">Max</span></span>
                    </a>

                    <!-- Right Side Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5 transition-all">
                            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707-.707zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                        </button>

                        <!-- Login Button -->
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-primary-700 hover:bg-primary-800 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Sign In
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-800 dark:from-primary-800 dark:to-primary-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="text-center">
                    <h1 class="text-3xl sm:text-4xl font-bold text-white mb-4">Our Products</h1>
                    <p class="text-primary-100 text-lg max-w-2xl mx-auto">Browse our collection of quality products.</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Filters Sidebar -->
                <aside class="w-full lg:w-64 flex-shrink-0">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-24">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                Filters
                            </h2>
                            @if(request()->hasAny(['search', 'category', 'min_price', 'max_price', 'sort']))
                                <a href="{{ route('guest.products') }}" class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 font-medium">Clear all</a>
                            @endif
                        </div>

                        <form method="GET" action="{{ route('guest.products') }}" class="space-y-5">
                            
                            <!-- Search -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input type="text" name="search" value="{{ request('search') }}" 
                                        class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                        placeholder="Product name, SKU...">
                                </div>
                            </div>

                            <!-- Categories -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                                <select name="category" class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price Range (Rs.)</label>
                                <div class="flex items-center space-x-2">
                                    <input type="number" name="min_price" value="{{ request('min_price') }}" 
                                        class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                        placeholder="Min">
                                    <span class="text-gray-500">-</span>
                                    <input type="number" name="max_price" value="{{ request('max_price') }}" 
                                        class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                        placeholder="Max">
                                </div>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                                <select name="sort" class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-primary-700 hover:bg-primary-800 text-white text-sm font-medium rounded-lg transition-colors shadow-sm flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Apply Filters
                            </button>
                        </form>
                    </div>
                </aside>

                <!-- Products Grid -->
                <div class="flex-1">
                    <!-- Results Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                        <p class="text-gray-600 dark:text-gray-400">
                            Showing <span class="font-semibold text-gray-900 dark:text-white">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span> of <span class="font-semibold text-gray-900 dark:text-white">{{ $products->total() }}</span> products
                        </p>
                        
                        <!-- Mobile Filter Toggle -->
                        <button type="button" onclick="document.getElementById('mobile-filters').classList.toggle('hidden')" class="lg:hidden inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                            Filters
                        </button>
                    </div>

                    <!-- Mobile Filters (Hidden by default) -->
                    <div id="mobile-filters" class="lg:hidden hidden mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                            <!-- Mobile filter form content - simplified -->
                            <form method="GET" action="{{ route('guest.products') }}" class="space-y-4">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                    class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Search products...">
                                <div class="flex gap-2">
                                    <select name="category" class="flex-1 py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="sort" class="flex-1 py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low</option>
                                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full py-2 bg-primary-700 text-white text-sm font-medium rounded-lg">Apply</button>
                            </form>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    @if($products->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($products as $product)
                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-300 group flex flex-col">
                                    <!-- Image -->
                                    <div class="relative h-52 bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                        @if($product->image)
                                            <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset($product->image) }}" 
                                                alt="{{ $product->name }}" 
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        
                                        <!-- Category Badge -->
                                        @if($product->category)
                                            <div class="absolute top-3 left-3">
                                                <span class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm text-gray-800 dark:text-gray-200 text-xs font-medium px-2.5 py-1 rounded-lg shadow-sm">
                                                    {{ $product->category->name }}
                                                </span>
                                            </div>
                                        @endif

                                        <!-- Stock Badge -->
                                        <div class="absolute top-3 right-3">
                                            @php
                                                $stockClass = $product->total_quantity > 10 ? 'bg-green-100 text-green-800 dark:bg-green-900/80 dark:text-green-300' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/80 dark:text-orange-300';
                                                $stockText = $product->total_quantity > 10 ? 'In Stock' : 'Low Stock';
                                            @endphp
                                            <span class="{{ $stockClass }} text-xs font-semibold px-2.5 py-1 rounded-lg shadow-sm">
                                                {{ $stockText }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-5 flex-1 flex flex-col">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1 line-clamp-1" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </h3>
                                        
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                            SKU: <span class="font-mono">{{ $product->sku }}</span>
                                        </p>
                                        
                                        @if($product->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-4 flex-1">
                                                {{ $product->description }}
                                            </p>
                                        @else
                                            <div class="flex-1"></div>
                                        @endif

                                        <!-- Variants/Price Info -->
                                        @if($product->variants->count() > 0)
                                            <div class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Starting from</p>
                                                        <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                                            Rs. {{ number_format($product->variants->min('selling_price'), 2) }}
                                                        </p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Available</p>
                                                        <p class="text-sm font-medium {{ $product->total_quantity < 10 ? 'text-orange-500' : 'text-gray-700 dark:text-gray-300' }}">
                                                            {{ $product->total_quantity }} {{ $product->variants->first()->unit->short_name ?? 'units' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                @if($product->variants->count() > 1)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                        {{ $product->variants->count() }} variants available
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No products found</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">We couldn't find any products matching your criteria. Try adjusting your filters or search terms.</p>
                            <a href="{{ route('guest.products') }}" class="inline-flex items-center px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Clear Filters
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Brand -->
                    <div>
                        <a href="{{ route('guest.products') }}" class="flex items-center space-x-3 mb-4">
                            <div class="bg-primary-600 p-1.5 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <span class="text-lg font-bold text-gray-800 dark:text-white">Shoppy<span class="text-primary-600 dark:text-primary-400">Max</span></span>
                        </a>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Quality products, competitive prices.</p>
                    </div>
                    
                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('guest.products') }}" class="text-sm text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 transition-colors">Shop</a></li>
                            <li><a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 transition-colors">Sign In</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Contact</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Have questions? Reach out to our team for assistance.</p>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <!-- Dark Mode Toggle Script -->
        <script>
            var themeToggleBtn = document.getElementById('theme-toggle');
            var darkIcon = document.getElementById('theme-toggle-dark-icon');
            var lightIcon = document.getElementById('theme-toggle-light-icon');

            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                lightIcon.classList.remove('hidden');
            } else {
                darkIcon.classList.remove('hidden');
            }

            themeToggleBtn.addEventListener('click', function() {
                darkIcon.classList.toggle('hidden');
                lightIcon.classList.toggle('hidden');

                if (localStorage.getItem('color-theme')) {
                    if (localStorage.getItem('color-theme') === 'light') {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    }
                } else {
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    }
                }
            });
        </script>
    </body>
</html>
