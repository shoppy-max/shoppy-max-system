<x-app-layout>
    <!-- Header & Breadcrumb -->
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Add Courier Payment') }}
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
                     <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('courier-payments.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Courier Payments</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Add Payment</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('courier-payments.store') }}">
        @csrf
        <div class="max-w-4xl mx-auto p-6 space-y-6">
            
            <!-- Payment Details Card -->
            <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">Payment Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Courier (Searchable Dropdown) -->
                    <div x-data="{ 
                        courierSearch: '', 
                        courierOpen: false, 
                        selectedCourier: null,
                        couriers: {{ json_encode($couriers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
                        get filteredCouriers() {
                            if (!this.courierSearch) return this.couriers;
                            return this.couriers.filter(c => 
                                c.name.toLowerCase().includes(this.courierSearch.toLowerCase())
                            );
                        },
                        selectCourier(courier) {
                            this.selectedCourier = courier;
                            this.courierSearch = courier.name;
                            this.courierOpen = false;
                        }
                    }">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Courier <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" 
                                       x-model="courierSearch"
                                       @input="courierOpen = true"
                                       @focus="courierOpen = true"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                       placeholder="Search courier..."
                                       required
                                       autocomplete="off">
                            </div>
                            
                            <!-- Dropdown -->
                            <div x-show="courierOpen" 
                                 x-transition
                                 @click.outside="courierOpen = false"
                                 class="absolute z-50 w-full bg-white dark:bg-gray-800 rounded-lg shadow-2xl border-2 border-purple-200 dark:border-purple-600 mt-2 max-h-60 overflow-y-auto">
                                <ul x-show="filteredCouriers.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="courier in filteredCouriers" :key="courier.id">
                                        <li @click="selectCourier(courier)" 
                                            class="px-4 py-3 hover:bg-purple-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="courier.name"></span>
                                        </li>
                                    </template>
                                </ul>
                                <div x-show="filteredCouriers.length === 0" class="p-4 text-center text-gray-500">
                                    No couriers found
                                </div>
                            </div>
                            <input type="hidden" name="courier_id" x-model="selectedCourier?.id">
                        </div>
                        @error('courier_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        @error('payment_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Amount (Rs.) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                               placeholder="0.00">
                        @error('amount')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Method</label>
                        <select name="payment_method" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select Method</option>
                            <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Bank Transfer" {{ old('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="Cheque" {{ old('payment_method') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="Card" {{ old('payment_method') == 'Card' ? 'selected' : '' }}>Card</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reference Number -->
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reference Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                               placeholder="e.g., TXN123456">
                        @error('reference_number')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Note -->
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Note</label>
                        <textarea name="payment_note" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                  placeholder="Additional notes about this payment...">{{ old('payment_note') }}</textarea>
                        @error('payment_note')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('courier-payments.index') }}" 
                   class="text-gray-700 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-800 border border-gray-300 dark:border-gray-600">
                    Cancel
                </a>
                <button type="submit" 
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Save Payment
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
