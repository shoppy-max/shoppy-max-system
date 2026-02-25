@php
    $selectedCourierIds = collect($selectedCouriers ?? [])->map(fn ($id) => (int) $id)->values()->all();
    $courierOptions = $couriers
        ->map(fn ($c) => [
            'id' => (int) $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
        ])
        ->values();
@endphp

<x-form-section title="Courier Details" description="Select allowed couriers for this {{ $entityLabel ?? 'reseller' }}. You can leave this empty, choose one, or choose multiple.">
    <div class="space-y-3" x-data='{
        open: false,
        search: "",
        options: @json($courierOptions),
        selected: @json($selectedCourierIds),
        filteredOptions() {
            const term = this.search.trim().toLowerCase();
            if (!term) return this.options;
            return this.options.filter(option => {
                const name = (option.name || "").toLowerCase();
                const phone = (option.phone || "").toLowerCase();
                return name.includes(term) || phone.includes(term);
            });
        },
        isSelected(id) {
            return this.selected.includes(Number(id));
        },
        toggle(id) {
            id = Number(id);
            if (this.isSelected(id)) {
                this.selected = this.selected.filter(selectedId => selectedId !== id);
                return;
            }
            this.selected = [...this.selected, id];
        },
        selectedOptions() {
            return this.options.filter(option => this.selected.includes(Number(option.id)));
        }
    }'>
        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Courier Allowlist</label>

        <div class="relative">
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.6-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @click="open = true"
                    placeholder="Search couriers by name or phone..."
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 pe-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                <button type="button" @click="open = !open" class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <div x-show="open" x-transition @click.outside="open = false" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto dark:bg-gray-700 dark:border-gray-600" style="display: none;">
                <template x-if="filteredOptions().length === 0">
                    <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">No couriers found.</div>
                </template>

                <template x-for="option in filteredOptions()" :key="`courier-option-${option.id}`">
                    <button type="button" @click="toggle(option.id)" class="w-full text-left px-4 py-2.5 hover:bg-blue-50 dark:hover:bg-gray-600 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="option.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-300" x-text="option.phone || 'No phone'"></p>
                        </div>
                        <svg x-show="isSelected(option.id)" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        <div x-show="selectedOptions().length > 0" class="flex flex-wrap gap-2 pt-1" style="display: none;">
            <template x-for="option in selectedOptions()" :key="`courier-selected-${option.id}`">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-medium dark:bg-blue-900 dark:text-blue-300">
                    <span x-text="option.name"></span>
                    <button type="button" @click="toggle(option.id)" class="text-blue-700 hover:text-blue-900 dark:text-blue-200 dark:hover:text-white">&times;</button>
                </span>
            </template>
        </div>

        <p x-show="selected.length === 0" class="text-xs text-gray-500 dark:text-gray-400">No couriers selected.</p>
        <p x-show="selected.length > 0" class="text-xs text-gray-500 dark:text-gray-400"><span x-text="selected.length"></span> courier(s) selected.</p>

        <template x-for="id in selected" :key="`courier-input-${id}`">
            <input type="hidden" name="couriers[]" :value="id">
        </template>

        @error('couriers')
            <p class="text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
        @error('couriers.*')
            <p class="text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>
</x-form-section>
