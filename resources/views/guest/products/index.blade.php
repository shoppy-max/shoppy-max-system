<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Available Products
                    </h1>
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-300 underline hover:text-blue-500">
                        Log in
                    </a>
                </div>
            </header>

            <!-- Main Content -->
            <main>
                <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    <!-- Search/Filter could go here -->
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 px-4 sm:px-0">
                        @forelse($products as $product)
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col hover:shadow-xl transition-shadow duration-300">
                                <!-- Image -->
                                <div class="relative h-48 w-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden group">
                                    @if($product->image)
                                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110">
                                    @else
                                        <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @endif
                                    
                                    <!-- Stock Badge -->
                                    <div class="absolute top-2 right-2">
                                        @if($product->quantity > 0)
                                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                                In Stock
                                            </span>
                                        @else
                                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">
                                                Out of Stock
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="p-4 flex-1 flex flex-col justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                            SKU: {{ $product->sku }}
                                        </p>
                                        @if($product->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-3">
                                                {{ $product->description }}
                                            </p>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Price</span>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                                Rs. {{ number_format($product->selling_price, 2) }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col text-right">
                                             <span class="text-xs text-gray-500 dark:text-gray-400">Available</span>
                                             <span class="font-medium {{ $product->quantity < 10 ? 'text-orange-500' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $product->quantity }} {{ $product->unit->short_name ?? '' }}
                                             </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-12 text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No products available</h3>
                                <p class="mt-1 text-gray-500 dark:text-gray-400">Check back later for new inventory.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6 px-4 sm:px-0">
                        {{ $products->links() }}
                    </div>
                </div>
            </main>
            
             <!-- Footer -->
             <footer class="bg-white dark:bg-gray-800 shadow mt-12">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
             </footer>
        </div>
    </body>
</html>
