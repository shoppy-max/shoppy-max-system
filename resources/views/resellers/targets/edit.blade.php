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
                            <a href="{{ route('reseller-targets.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Reseller Targets</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Edit Target</h2>
        </div>

        <form method="POST" action="{{ route('reseller-targets.update', $resellerTarget) }}" x-data="targetForm()">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <x-form-section title="Target Details" description="Define the reseller and target parameters.">
                        <div class="space-y-6">
                            <!-- Reseller Select (Searchable) -->
                            <div class="relative" x-data="searchableSelect({
                                options: @js($resellers->map(fn($r) => ['id' => $r->id, 'text' => ($r->business_name ?: $r->name) . ' - ' . $r->name])),
                                selected: '{{ old('reseller_id', $resellerTarget->reseller_id) }}',
                                name: 'reseller_id'
                            })">
                                <label for="reseller_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reseller <span class="text-red-500">*</span></label>
                                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Only regular resellers are available here. Direct resellers are excluded.</p>
                                
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
                                                <span x-text="option.text"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No results found</div>
                                    </div>
                                </div>
                                @error('reseller_id') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="target_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Target Type <span class="text-red-500">*</span></label>
                                    <select id="target_type" name="target_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                        <option value="daily" {{ (old('target_type') ?? $resellerTarget->target_type) == 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ (old('target_type') ?? $resellerTarget->target_type) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ (old('target_type') ?? $resellerTarget->target_type) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('target_type')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="target_pcs_qty" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Target Pieces Qty <span class="text-red-500">*</span></label>
                                    <input type="number" id="target_pcs_qty" name="target_pcs_qty" value="{{ old('target_pcs_qty', $resellerTarget->target_pcs_qty) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required min="1">
                                    <x-input-error :messages="$errors->get('target_pcs_qty')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div>
                                <label for="ref_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reference ID</label>
                                <input type="text" id="ref_id" name="ref_id" value="{{ old('ref_id', $resellerTarget->ref_id) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <x-input-error :messages="$errors->get('ref_id')" class="mt-2" />
                            </div>
                        </div>
                    </x-form-section>

                    <x-form-section title="Timeline" description="Set the duration for this target.">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="start_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', optional($resellerTarget->start_date)->format('Y-m-d')) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>

                            <div>
                                <label for="end_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', optional($resellerTarget->end_date)->format('Y-m-d')) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                        </div>
                    </x-form-section>

                </div>

                <!-- Right Column (1/3) -->
                <div class="space-y-6">
                    <x-form-section title="Financials">
                         <div class="space-y-4">
                            <div>
                                <label for="target_completed_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Target Amount</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Rs.</span>
                                    </div>
                                    <input type="number" step="0.01" id="target_completed_price" name="target_completed_price" value="{{ old('target_completed_price', $resellerTarget->target_completed_price) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-12 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>
                                <x-input-error :messages="$errors->get('target_completed_price')" class="mt-2" />
                            </div>

                            <div>
                                <label for="with_completed_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">With Completed Price</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Rs.</span>
                                    </div>
                                    <input type="number" step="0.01" id="with_completed_price" name="with_completed_price" value="{{ old('with_completed_price', $resellerTarget->with_completed_price) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-12 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>
                                <x-input-error :messages="$errors->get('with_completed_price')" class="mt-2" />
                            </div>
                            
                             <div>
                                <label for="return_order_target_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Return Order Limit</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Rs.</span>
                                    </div>
                                    <input type="number" step="0.01" id="return_order_target_price" name="return_order_target_price" value="{{ old('return_order_target_price', $resellerTarget->return_order_target_price) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-12 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>
                                <x-input-error :messages="$errors->get('return_order_target_price')" class="mt-2" />
                            </div>
                        </div>
                    </x-form-section>

                    <div class="flex flex-col gap-4">
                        <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 shadow-lg hover:shadow-xl transition-shadow flex justify-center items-center">
                            Update Target
                        </button>
                        <a href="{{ route('reseller-targets.index') }}" class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 text-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-layout>
    
    <script>
        function targetForm() {
            return {}
        }

        function searchableSelect(config) {
            return {
                open: false,
                search: '',
                selected: config.selected || '',
                options: config.options,
                
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
                    this.selected = option.id.toString();
                    this.open = false;
                    this.search = '';
                }
            }
        }
    </script>
</x-app-layout>
