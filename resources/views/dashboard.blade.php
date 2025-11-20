<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">{{ __("Welcome, ") }}{{ Auth::user()->name }}!</h3>
                    <p class="mb-4">{{ __("You're logged in!") }}</p>
                    
                    <div class="mt-6">
                        <h4 class="font-medium text-gray-700 mb-2">{{ __('Your Roles:') }}</h4>
                        <div class="flex flex-wrap gap-2">
                            @forelse(Auth::user()->roles as $role)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-gray-500">{{ __('No roles assigned') }}</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-medium text-gray-700 mb-2">{{ __('Your Permissions:') }}</h4>
                        <div class="flex flex-wrap gap-2">
                            @forelse(Auth::user()->getAllPermissions() as $permission)
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                    {{ $permission->name }}
                                </span>
                            @empty
                                <span class="text-gray-500">{{ __('No permissions assigned') }}</span>
                            @endforelse
                        </div>
                    </div>

                    @role('super admin')
                    <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-yellow-800 font-semibold">
                            {{ __('🔑 Super Admin Access') }}
                        </p>
                        <p class="text-yellow-700 text-sm mt-1">
                            {{ __('You have full access to the admin panel. Use the navigation menu above to manage users, roles, and permissions.') }}
                        </p>
                    </div>
                    @endrole
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
