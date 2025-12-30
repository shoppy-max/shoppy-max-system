<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        
        <!-- Dark Mode Toggle Script -->
        <script>
            // On page load or when changing themes, best to add inline in `head` to avoid FOUC
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content (Pushed right by sidebar on desktop) -->
        <div class="p-4 sm:ml-64 min-h-screen flex flex-col">
            
            <!-- Page Heading -->
            @isset($header)
                <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    {{ $header }}
                </div>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Check for dark mode function
                const isDarkMode = () => document.documentElement.classList.contains('dark');

                // 1. Global Delete Confirmation Listener
                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    // Check if the form has our data attribute
                    if (form && form.hasAttribute('data-confirm-message')) {
                        e.preventDefault(); // Stop submission immediately
                        
                        const message = form.getAttribute('data-confirm-message');

                        // Ensure Swal is available
                        if (typeof window.Swal === 'undefined') {
                            console.error('SweetAlert2 is not loaded!');
                            if (confirm(message)) {
                                form.submit(); // Fallback
                            }
                            return;
                        }

                        window.Swal.fire({
                            title: 'Are you sure?',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!',
                            background: isDarkMode() ? '#1f2937' : '#fff',
                            color: isDarkMode() ? '#fff' : '#1f2937'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Temporarily remove attribute to allow submission
                                form.removeAttribute('data-confirm-message');
                                form.submit();
                            }
                        });
                    }
                });

                // 2. Strip native onsubmit (if any still exist)
                document.querySelectorAll('form[onsubmit]').forEach(form => {
                     const onsubmit = form.getAttribute('onsubmit');
                     if (onsubmit && onsubmit.includes('confirm')) {
                         const match = onsubmit.match(/confirm\('([^']+)'\)/);
                         const message = (match && match[1]) ? match[1] : 'You won\'t be able to revert this!';
                         form.setAttribute('data-confirm-message', message);
                         form.removeAttribute('onsubmit');
                     }
                });
            });

            // 3. Session Flash Messages (Wait for window.onload to ensure Swal is ready)
            window.onload = function() {
                if (typeof window.Swal !== 'undefined') {
                     const isDarkMode = () => document.documentElement.classList.contains('dark');
                     const Toast = window.Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', window.Swal.stopTimer)
                            toast.addEventListener('mouseleave', window.Swal.resumeTimer)
                        }
                    });

                    @if(session('success'))
                        Toast.fire({
                            icon: 'success',
                            title: "{{ session('success') }}",
                            background: isDarkMode() ? '#1f2937' : '#fff',
                            color: isDarkMode() ? '#fff' : '#1f2937'
                        });
                    @endif

                    @if(session('error'))
                        Toast.fire({
                            icon: 'error',
                            title: "{{ session('error') }}",
                            background: isDarkMode() ? '#1f2937' : '#fff',
                            color: isDarkMode() ? '#fff' : '#1f2937'
                        });
                    @endif
                }
            };
        </script>
        @stack('scripts')
    </body>
</html>
