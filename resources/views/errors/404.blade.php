<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | {{ config('app.name', 'ShoppyMax') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-24 -right-20 h-72 w-72 rounded-full bg-primary-300/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-blue-300/20 blur-3xl"></div>
        </div>

        <section class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">Error 404</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Page Not Found</h1>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                The page you requested does not exist or may have been moved.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500">
                        Go to Login
                    </a>
                @endauth
                <a href="{{ route('guest.products') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    View Products
                </a>
            </div>
        </section>
    </main>
</body>
</html>
