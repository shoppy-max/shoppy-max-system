<x-app-layout>
    <x-form-layout>
        <div class="mb-6">
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
                            <a href="{{ route('reseller-payments.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">User Payments</a>
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
            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Add New Payment</h2>
        </div>

        <form method="POST" action="{{ route('reseller-payments.store') }}" x-data="paymentForm()">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <x-form-section title="Payment Details" description="Enter payment information to update reseller balance.">
                        <div class="space-y-6">
                            <!-- Reseller Select (Searchable) -->
                            <div class="relative" x-data="searchableSelect({
                                options: @js($resellers->map(fn($r) => ['id' => $r->id, 'text' => $r->business_name . ' (' . $r->name . ')', 'due' => $r->due_amount])),
                                selected: '{{ old('reseller_id') }}',
                                name: 'reseller_id'
                            })" x-on:selected-reseller.window="updateDue($event.detail)">
                                <label for="reseller_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reseller <span class="text-red-500">*</span></label>
                                
                                <input type="hidden" name="reseller_id" :value="selected">
                                
                                <div class="relative" @click.away="open = false">
                                    <div @click="open = !open" 
                                         class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 cursor-pointer flex justify-between items-center">
                                        <span x-text="selectedText() || 'Select Reseller'"></span>
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>

                                    <div x-show="open" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto" style="display: none;">
                                        <div class="p-2 sticky top-0 bg-white dark:bg-gray-700">
                                            <input type="text" x-model="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" placeholder="Search...">
                                        </div>
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <div @click="select(option)" class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                                <div class="flex justify-between">
                                                    <span x-text="option.text"></span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'Due: Rs. ' + (option.due || 0)"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No results found</div>
                                    </div>
                                </div>
                                @error('reseller_id') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <!-- Amount -->
                            <div>
                                <label for="amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Amount (Rs.) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" id="amount" name="amount" x-model="amount" value="{{ old('amount') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required placeholder="0.00">
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="payment_method" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Method <span class="text-red-500">*</span></label>
                                    <select id="payment_method" name="payment_method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                        <option value="bank" {{ old('payment_method') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="payment_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Date <span class="text-red-500">*</span></label>
                                    <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                    <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div>
                                <label for="reference_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reference ID / Note</label>
                                <input type="text" id="reference_id" name="reference_id" value="{{ old('reference_id') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Cheque number, transaction ID, etc.">
                                <x-input-error :messages="$errors->get('reference_id')" class="mt-2" />
                            </div>
                        </div>
                    </x-form-section>
                </div>

                <!-- Right Column (1/3) -->
                <div class="space-y-6">
                    <x-form-section title="Balance Summary">
                        <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Current Due:</span>
                                <span class="text-lg font-bold text-gray-900 dark:text-white" x-text="'Rs. ' + parseFloat(currentDue).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between items-center text-green-600 dark:text-green-400">
                                <span class="text-sm">Paid Amount:</span>
                                <span class="text-lg font-bold" x-text="'(-) Rs. ' + (parseFloat(amount) || 0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-600">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">New Balance:</span>
                                <span class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="'Rs. ' + calculateBalance()"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                             The reseller's due amount will be automatically updated upon saving.
                        </p>
                    </x-form-section>

                    <div class="flex flex-col gap-4">
                        <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 shadow-lg hover:shadow-xl transition-shadow flex justify-center items-center">
                            Save Payment
                        </button>
                        <a href="{{ route('reseller-payments.index') }}" class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 text-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-layout>
    
    <script>
        function paymentForm() {
            return {
                currentDue: 0,
                amount: '',
                
                updateDue(details) {
                    this.currentDue = details.due || 0;
                },
                
                calculateBalance() {
                    let due = parseFloat(this.currentDue);
                    let paid = parseFloat(this.amount) || 0;
                    return (due - paid).toFixed(2);
                }
            }
        }

        function searchableSelect(config) {
            return {
                open: false,
                search: '',
                selected: config.selected || '',
                options: config.options,
                
                init() {
                     if(this.selected) {
                         // Find initial selection to trigger update
                         const initial = this.options.find(o => o.id == this.selected);
                         if(initial) {
                             this.$dispatch('selected-reseller', initial);
                         }
                     }
                },

                get filteredOptions() {
                    if (this.search === '') {
                        return this.options;
                    }
                    return this.options.filter(option => {
                        return option.text.toLowerCase().includes(this.search.toLowerCase());
                    });
                },
                
                selectedText() {
                    const option = this.options.find(o => o.id == this.selected);
                    return option ? option.text : '';
                },
                
                select(option) {
                    this.selected = option.id;
                    this.open = false;
                    this.search = '';
                    this.$dispatch('selected-reseller', option);
                }
            }
        }
    </script>
</x-app-layout>
