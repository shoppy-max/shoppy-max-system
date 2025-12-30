<x-app-layout>
    @php
        $initialProvince = old('province');
        $initialDistricts = $initialProvince && isset($slData[$initialProvince]) ? $slData[$initialProvince] : [];
    @endphp
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Add New Supplier') }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <a href="{{ route('suppliers.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Suppliers</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Create</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-lg dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('suppliers.store') }}" method="POST"
                  x-data="supplierForm()"
                  class="space-y-6">
                @csrf

                <!-- Section: Basic Info -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Supplier Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Supplier Name" required>
                            @error('name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Business Name -->
                        <div>
                            <label for="business_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Business Name</label>
                            <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Business Name">
                            @error('business_name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section: Contact Info -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4 flex items-center">
                         <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Mobile -->
                        <div>
                            <label for="mobile" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mobile <span class="text-red-500">*</span></label>
                            <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Mobile Number" required>
                             @error('mobile') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Landline -->
                        <div>
                            <label for="landline" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Landline</label>
                            <input type="text" name="landline" id="landline" value="{{ old('landline') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Landline Number">
                             @error('landline') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                         <!-- Email -->
                         <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Email Address">
                             @error('email') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section: Address -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Address Details
                    </h3>
                    
                    <div class="mb-4">
                        <label for="address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address / Street <span class="text-red-500">*</span></label>
                        <input type="text" name="address" id="address" value="{{ old('address') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Street Address" required>
                         @error('address') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Country -->
                         <div>
                            <label for="country" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Country <span class="text-red-500">*</span></label>
                            <select name="country" id="country" x-model="country" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                @foreach(config('locations.countries') as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                             @error('country') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                         <!-- City -->
                        <div>
                            <label for="city" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">City <span class="text-red-500">*</span></label>
                            
                            <!-- Unified Input with Datalist -->
                            <input list="city_options" type="text" name="city" id="city" x-model="city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter or Select City" required>
                            
                            <datalist id="city_options">
                                <template x-for="c in availableCities" :key="c.city_name">
                                    <option :value="c.city_name"></option>
                                </template>
                            </datalist>

                             @error('city') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Sri Lanka Specific Fields -->
                    <div x-show="country === 'Sri Lanka'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <!-- Province -->
                        <div>
                            <label for="province" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Province</label>
                            <select name="province" id="province" x-model="province" @change="handleProvinceChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">Select Province</option>
                                @foreach($slData as $prov => $dists)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                             @error('province') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- District -->
                        <div>
                            <label for="district" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">District</label>
                            <select name="district" id="district" x-model="district" @change="handleDistrictChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">Select District</option>
                                <template x-for="dist in availableDistricts" :key="dist">
                                     <option :value="dist" x-text="dist" :selected="dist === district"></option>
                                </template>
                            </select>
                             @error('district') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('suppliers.index') }}" class="py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Cancel</a>
                    <button type="submit" class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800 shadow-lg hover:shadow-xl transition-shadow">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

    <script>
        function supplierForm() {
            return {
                country: '{{ old('country', 'Sri Lanka') }}',
                province: '{{ $initialProvince }}',
                district: '{{ old('district') }}',
                city: '{{ old('city') }}',
                provinces: @json($slData),
                // Flatten all districts for initial "All" view or specific logic
                allDistricts: @json(collect($slData)->flatten()), 
                availableDistricts: [],
                availableCities: [],
                
                init() {
                    this.updateDistricts();
                    if (this.country === 'Sri Lanka' && this.district) {
                         this.fetchCities();
                    }
                },
                
                handleCountryChange() {
                    if (this.country !== 'Sri Lanka') {
                        this.province = '';
                        this.district = '';
                        this.city = '';
                    }
                },

                handleProvinceChange() {
                     // Filter districts based on province
                     this.updateDistricts();
                     this.district = ''; // Reset district when province changes
                     this.city = '';
                     this.availableCities = [];
                },

                handleDistrictChange() {
                     // If province is empty, try to find it from the selected district
                     if (!this.province && this.district) {
                         this.findProvinceByDistrict(this.district);
                     }
                     this.fetchCities();
                     this.city = '';
                },

                updateDistricts() {
                    if (this.province && this.provinces[this.province]) {
                        this.availableDistricts = this.provinces[this.province];
                    } else {
                        // If no province selected, show ALL districts
                         this.availableDistricts = this.allDistricts;
                    }
                },
                
                findProvinceByDistrict(dist) {
                    for (const [prov, dists] of Object.entries(this.provinces)) {
                        if (dists.includes(dist)) {
                            this.province = prov;
                            this.availableDistricts = dists; // Narrow down the list now
                            return;
                        }
                    }
                },

                async fetchCities() {
                    this.availableCities = [];
                    if (!this.district) return;

                    try {
                        let response = await fetch(`/api/cities?district=${this.district}`);
                        let data = await response.json();
                        this.availableCities = data;
                    } catch (error) {
                        console.error('Failed to fetch cities:', error);
                    }
                     // Keep user input if they typed something, or clear? Better keep.
                }
            }
        }
    </script>
