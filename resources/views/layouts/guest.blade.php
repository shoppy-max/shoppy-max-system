<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ShoppyMax') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            // Check for saved theme preference
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">
            
            <!-- Background decorative elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-400/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-400/20 rounded-full blur-3xl"></div>
            </div>

            <!-- View Product Link -->
            <div class="absolute top-0 right-0 p-6 z-10">
                <a href="{{ route('guest.products') }}" class="inline-flex items-center px-5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-primary-700 dark:text-primary-400 rounded-lg text-sm font-medium shadow-sm border border-gray-200 dark:border-gray-700 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    View Products
                </a>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="absolute top-0 left-0 p-6 z-10">
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5 transition-all duration-200 shadow-sm">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707-.707zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            
            <!-- Logo Section -->
            <div class="relative z-10 mb-8 text-center">
                <a href="/" class="flex flex-col items-center group">
                    <div class="bg-primary-600 p-3 rounded-2xl shadow-lg shadow-primary-500/30 group-hover:shadow-primary-500/50 group-hover:scale-105 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <span class="mt-4 text-2xl font-bold text-gray-800 dark:text-white tracking-tight">Shoppy<span class="text-primary-600 dark:text-primary-400">Max</span></span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">Inventory Management System</span>
                </a>
            </div>

            <!-- Main Card -->
            <div class="relative z-10 w-full sm:max-w-md px-6 py-8 bg-white dark:bg-gray-800 shadow-xl overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <div class="relative z-10 mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }} ShoppyMax. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Dark Mode Toggle Script -->
        <script>
            var themeToggleBtn = document.getElementById('theme-toggle');
            var darkIcon = document.getElementById('theme-toggle-dark-icon');
            var lightIcon = document.getElementById('theme-toggle-light-icon');

            // Change the icons inside the button based on previous settings
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                lightIcon.classList.remove('hidden');
            } else {
                darkIcon.classList.remove('hidden');
            }

            themeToggleBtn.addEventListener('click', function() {
                // toggle icons inside button
                darkIcon.classList.toggle('hidden');
                lightIcon.classList.toggle('hidden');

                // if set via local storage previously
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
