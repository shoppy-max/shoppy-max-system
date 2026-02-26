<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Direct Reseller Profile') }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('direct-resellers.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Direct Resellers</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Profile</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        
        <!-- Hero Header Card -->
        <div class="relative overflow-hidden bg-white border border-gray-200 rounded-xl shadow-lg dark:bg-gray-800 dark:border-gray-700">
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
            <div class="relative pt-16 px-6 pb-6">
                <div class="flex flex-col md:flex-row items-start md:items-end justify-between space-y-4 md:space-y-0">
                    <div class="flex items-end space-x-5">
                        <div class="w-24 h-24 rounded-xl bg-white p-1 shadow-lg dark:bg-gray-800">
                             <div class="w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-3xl font-bold text-gray-400 dark:text-gray-500">
                                {{ substr($reseller->name, 0, 1) }}
                             </div>
                        </div>
                        <div class="mb-1">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $reseller->name }}</h1>
                             <p class="text-lg text-gray-600 dark:text-gray-300 font-medium">{{ $reseller->business_name }}</p>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                         <a href="{{ route('direct-resellers.edit', $reseller) }}" class="flex items-center justify-center text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:focus:ring-yellow-900 shadow-md transition-transform transform hover:scale-105">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Edit
                        </a>
                        <form action="{{ route('direct-resellers.destroy', $reseller) }}" method="POST" data-confirm-message="Delete this direct reseller?" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex items-center justify-center text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900 shadow-md transition-transform transform hover:scale-105">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Stats & Status (1/3) -->
            <div class="space-y-6">
                <!-- Status Card -->
                 <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-500 uppercase dark:text-gray-400 mb-4">Account Status</h4>
                    <div class="flex items-center justify-between">
                         <span class="text-gray-900 dark:text-white font-medium">Current Balance</span>
                         @if($reseller->due_amount > 0)
                            <span class="bg-red-100 text-red-800 text-sm font-bold px-3 py-1 rounded-full dark:bg-red-900 dark:text-red-300">
                                Due: Rs {{ number_format($reseller->due_amount, 2) }}
                            </span>
                        @else
                            <span class="bg-green-100 text-green-800 text-sm font-bold px-3 py-1 rounded-full dark:bg-green-900 dark:text-green-300">
                                Fully Paid
                            </span>
                        @endif
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">District</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $reseller->district ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details (2/3) -->
            <div class="lg:col-span-2">
                 <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 h-full">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        Contact & Location Details
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mobile Number</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $reseller->mobile }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Landline</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $reseller->landline ?? '-' }}</p>
                        </div>

                         <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Address</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $reseller->email ?? '-' }}</p>
                        </div>

                        <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                             <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Address</p>
                             <p class="mt-1 text-base text-gray-900 dark:text-white leading-relaxed">
                                {{ $reseller->address }}<br>
                                {{ $reseller->city }}<br>
                                @if($reseller->country === 'Sri Lanka')
                                    {{ $reseller->district }}, {{ $reseller->province }}<br>
                                @endif
                                {{ $reseller->country }}
                             </p>
                        </div>

                        <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                             <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Allowed Couriers</p>
                             @if($reseller->couriers->isEmpty())
                                <p class="mt-1 text-base text-gray-500 dark:text-gray-400">No courier allowlist configured.</p>
                             @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($reseller->couriers as $courier)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ $courier->name }}
                                        </span>
                                    @endforeach
                                </div>
                             @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
