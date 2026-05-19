<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Couriers') }}
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
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Couriers</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800" x-data="courierWaybillManager()">
        
        <!-- Stats Widgets (Simplified for Couriers) -->
        <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-3">
            <x-stats-card title="Total Couriers" value="{{ $couriers->total() }}" color="blue">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </x-stats-card>
            
            <x-stats-card title="Active Couriers" value="{{ \App\Models\Courier::where('is_active', true)->count() }}" color="green">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </x-stats-card>
        </div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-4 mb-6">
            <!-- Search Bar -->
            <div class="flex-1 w-full md:max-w-lg">
                <form method="GET" action="{{ route('couriers.index') }}" class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search couriers..." />
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                @can('create couriers')
                <a href="{{ route('couriers.create') }}" class="flex items-center justify-center text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800 transition-transform transform hover:scale-105 shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Courier
                </a>
                @endcan
            </div>
        </div>

        <div class="relative overflow-x-auto sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Courier Name</th>
                        <th scope="col" class="px-6 py-3">Contact Details</th>
                        <th scope="col" class="px-6 py-3">Address</th>
                        <th scope="col" class="px-6 py-3">Charges</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($couriers as $courier)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $courier->name }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    @if($courier->phone)
                                        <div class="flex items-center gap-2 mb-1">
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $courier->phone }}</span>
                                        </div>
                                    @endif
                                    @if($courier->email)
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            <span class="text-xs text-gray-500">{{ $courier->email }}</span>
                                        </div>
                                    @endif
                                    @if(!$courier->phone && !$courier->email)
                                        <span class="text-gray-400 text-xs italic">No contact info</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($courier->address)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block max-w-xs truncate" title="{{ $courier->address }}">
                                        {{ $courier->address }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if(!empty($courier->rates) && is_array($courier->rates))
                                    <div class="flex flex-wrap gap-1.5 max-w-xs">
                                        @foreach($courier->rates as $rate)
                                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs dark:bg-blue-900 dark:text-blue-200">
                                                Rs. {{ number_format((float) $rate, 2) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">No set values</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($courier->is_active)
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Active</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    @can('manage courier waybills')
                                    <button
                                        type="button"
                                        @click="openWaybillModal({ id: {{ $courier->id }}, name: @js($courier->name) })"
                                        class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg dark:text-blue-400 dark:hover:bg-gray-700 transition-colors"
                                        title="Waybill IDs"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </button>
                                    @endcan
                                    @can('edit couriers')
                                    <a href="{{ route('couriers.edit', $courier) }}" class="p-2 text-yellow-600 hover:bg-yellow-100 rounded-lg dark:text-yellow-400 dark:hover:bg-gray-700 transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    @endcan
                                    @can('delete couriers')
                                    <form action="{{ route('couriers.destroy', $courier) }}" method="POST" data-confirm-message="Are you sure you want to delete this courier?" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg dark:text-red-400 dark:hover:bg-gray-700 transition-colors" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-base">No couriers found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $couriers->links() }}
        </div>

        @can('manage courier waybills')
        <div
            x-show="showWaybillModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-3 sm:p-4"
            x-transition.opacity
        >
            <div @click.away="closeWaybillModal()" class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-800">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Waybill IDs</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedCourier ? `${selectedCourier.name} waybill pool and allocation history.` : ''"></p>
                    </div>
                    <button type="button" @click="closeWaybillModal()" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="grid grid-cols-1 gap-5 p-4 sm:p-6 xl:grid-cols-12">
                    <div class="xl:col-span-4 xl:max-w-sm">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/30">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Add Waybill Range</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Prefix and suffix are optional. Start and end numbers are required, whole numbers only, and end must be larger than start.</p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prefix</label>
                                    <input x-model="rangeForm.prefix" type="text" class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="SPX-">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Start Number</label>
                                        <input x-model="rangeForm.start_number" type="number" min="0" step="1" class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="240001">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">End Number</label>
                                        <input x-model="rangeForm.end_number" type="number" min="1" step="1" class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="240100">
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Suffix</label>
                                    <input x-model="rangeForm.suffix" type="text" class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="-A">
                                </div>
                                <button
                                    type="button"
                                    @click="submitWaybillRange()"
                                    :disabled="savingWaybillRange || loadingWaybills"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-700"
                                >
                                    <span x-show="!savingWaybillRange">Add Range</span>
                                    <span x-show="savingWaybillRange">Saving...</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/20">
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" x-text="summary.total_waybills ?? 0"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/20">
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Available</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300" x-text="summary.available_waybills ?? 0"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/20 sm:col-span-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Next Available</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white break-all" x-text="summary.next_available_waybill || 'None'"></p>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-8 xl:min-w-0">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-900/20">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Current Waybill IDs</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Allocated rows show which order already consumed that waybill ID.</p>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="pagination.total ? `${pagination.total} total` : '0 total'"></div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-3">Waybill ID</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3">Order</th>
                                            <th class="px-4 py-3">Allocated At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="loadingWaybills">
                                            <tr>
                                                <td colspan="4" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Loading waybill IDs...</td>
                                            </tr>
                                        </template>
                                        <template x-if="!loadingWaybills && waybillItems.length === 0">
                                            <tr>
                                                <td colspan="4" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No waybill IDs added for this courier yet.</td>
                                            </tr>
                                        </template>
                                        <template x-for="item in waybillItems" :key="item.id">
                                            <tr class="border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                                <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white" x-text="item.code"></td>
                                                <td class="px-4 py-3">
                                                    <span
                                                        class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                                        :class="item.status === 'allocated' ? 'border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'"
                                                        x-text="item.status === 'allocated' ? 'Allocated' : 'Available'"
                                                    ></span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="item.order_number || '-'"></td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="item.allocated_at || '-'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-900/20">
                                <button
                                    type="button"
                                    @click="loadWaybillPage(pagination.current_page - 1)"
                                    :disabled="loadingWaybills || pagination.current_page <= 1"
                                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    Previous
                                </button>
                                <span class="text-sm text-gray-600 dark:text-gray-300" x-text="pagination.last_page ? `Page ${pagination.current_page} of ${pagination.last_page}` : 'Page 1 of 1'"></span>
                                <button
                                    type="button"
                                    @click="loadWaybillPage(pagination.current_page + 1)"
                                    :disabled="loadingWaybills || pagination.current_page >= pagination.last_page"
                                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <script>
        function courierWaybillManager() {
            return {
                showWaybillModal: false,
                selectedCourier: null,
                loadingWaybills: false,
                savingWaybillRange: false,
                waybillItems: [],
                summary: {},
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    total: 0,
                },
                rangeForm: {
                    prefix: '',
                    start_number: '',
                    end_number: '',
                    suffix: '',
                },
                getWaybillUrl(page = 1) {
                    if (!this.selectedCourier) {
                        return null;
                    }

                    const base = @js(url('/couriers/__COURIER__/waybills'));
                    return `${base.replace('__COURIER__', this.selectedCourier.id)}?page=${page}`;
                },
                notify(type, message) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: type,
                            text: message,
                            toast: true,
                            timer: 2500,
                            showConfirmButton: false,
                            position: 'top-end',
                        });
                        return;
                    }

                    alert(message);
                },
                openWaybillModal(courier) {
                    this.selectedCourier = courier;
                    this.showWaybillModal = true;
                    this.rangeForm = { prefix: '', start_number: '', end_number: '', suffix: '' };
                    this.loadWaybillPage(1);
                },
                closeWaybillModal() {
                    this.showWaybillModal = false;
                    this.selectedCourier = null;
                    this.waybillItems = [];
                    this.summary = {};
                    this.pagination = { current_page: 1, last_page: 1, total: 0 };
                },
                async loadWaybillPage(page = 1) {
                    const url = this.getWaybillUrl(page);
                    if (!url) {
                        return;
                    }

                    this.loadingWaybills = true;

                    try {
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Failed to load waybill IDs.');
                        }

                        this.waybillItems = Array.isArray(data.items) ? data.items : [];
                        this.summary = data.summary || {};
                        this.pagination = data.pagination || { current_page: 1, last_page: 1, total: 0 };
                    } catch (error) {
                        this.notify('error', error.message || 'Failed to load waybill IDs.');
                    } finally {
                        this.loadingWaybills = false;
                    }
                },
                async submitWaybillRange() {
                    if (!this.selectedCourier) {
                        return;
                    }

                    const startNumber = Number(this.rangeForm.start_number);
                    const endNumber = Number(this.rangeForm.end_number);

                    if (!Number.isInteger(startNumber) || !Number.isInteger(endNumber)) {
                        this.notify('warning', 'Start and end numbers must be whole numbers.');
                        return;
                    }

                    if (endNumber <= startNumber) {
                        this.notify('warning', 'End number must be larger than start number.');
                        return;
                    }

                    this.savingWaybillRange = true;

                    try {
                        const response = await fetch(this.getWaybillUrl(), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': @js(csrf_token()),
                            },
                            body: JSON.stringify(this.rangeForm),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            const message = data?.message
                                || Object.values(data?.errors || {}).flat()[0]
                                || 'Failed to add waybill IDs.';
                            throw new Error(message);
                        }

                        this.notify('success', data.message || 'Waybill IDs added successfully.');
                        this.rangeForm = { prefix: '', start_number: '', end_number: '', suffix: '' };
                        await this.loadWaybillPage(1);
                    } catch (error) {
                        this.notify('error', error.message || 'Failed to add waybill IDs.');
                    } finally {
                        this.savingWaybillRange = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
