<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Welcome Section with Roles -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl mb-6 border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">{{ __("Welcome back, ") }}<span class="text-primary-600 dark:text-primary-400">{{ Auth::user()->name }}</span>! 👋</h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ __("Here's what's happening with your store today.") }}</p>
                        </div>
                        <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
                            @forelse(Auth::user()->roles as $role)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200 rounded-full text-sm font-medium border border-blue-200 dark:border-blue-800">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-gray-500 dark:text-gray-400 text-sm italic">{{ __('No roles assigned') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            
            @can('view users')
            <div class="mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-lg flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                        {{ __('Admin Access Granted') }}
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                        <p>{{ __('You have elevated privileges.') }}</p>
                    </div>
                </div>
            </div>
            @endcan

        </div>
    </div>
</x-app-layout>
