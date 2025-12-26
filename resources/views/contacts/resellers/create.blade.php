<x-app-layout>
    @php
        $initialProvince = old('province');
        $initialDistricts = $initialProvince && isset($slData[$initialProvince]) ? $slData[$initialProvince] : [];
    @endphp
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Add New Reseller') }}
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
                            <a href="{{ route('resellers.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Resellers</a>
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
            <form action="{{ route('resellers.store') }}" method="POST"
                  x-data="resellerForm()"
                  class="space-y-6">
                @csrf

                <!-- Section: Basic Info -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Reseller Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Reseller Name" required>
                            @error('name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Business Name -->
                        <div>
                            <label for="business_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Business Name</label>
                            <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Business Name" required>
                            @error('business_name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Due Amount -->
                        <div>
                             <label for="due_amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Due Amount</label>
                             <input type="number" step="0.01" name="due_amount" id="due_amount" value="{{ old('due_amount', 0) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                             @error('due_amount') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
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
                            
                            <!-- Dynamic Dropdown for Sri Lanka -->
                            <div x-show="country === 'Sri Lanka'">
                                <select name="city" id="city_select" x-model="city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" :required="country === 'Sri Lanka'">
                                    <option value="">Select City</option>
                                    <template x-for="c in availableCities" :key="c.city_name">
                                        <option :value="c.city_name" x-text="c.city_name" :selected="c.city_name === city"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Standard Input for Other Countries -->
                            <div x-show="country !== 'Sri Lanka'">
                                <input type="text" name="city" id="city_input" x-model="city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter City" :required="country !== 'Sri Lanka'">
                            </div>

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
                                {{-- Render provinces server-side for stability --}}
                                @foreach($slData as $prov => $dists)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                             @error('province') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- District -->
                        <div>
                            <label for="district" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">District</label>
                            
                            <!-- Static Select for Initial Load (Blade) -->
                            <template x-if="!isDynamic">
                                <select name="district" id="district" x-model="district" @change="handleDistrictChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option value="">Select District</option>
                                    @foreach($initialDistricts as $dist)
                                        <option value="{{ $dist }}" {{ old('district') == $dist ? 'selected' : '' }}>{{ $dist }}</option>
                                    @endforeach
                                </select>
                            </template>

                            <!-- Dynamic Select for Updates (Alpine) -->
                            <template x-if="isDynamic">
                                <select name="district" id="district_dynamic" x-model="district" @change="handleDistrictChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option value="">Select District</option>
                                    <template x-for="dist in availableDistricts" :key="dist">
                                         <option :value="dist" x-text="dist" :selected="dist === district"></option>
                                    </template>
                                </select>
                            </template>
                             @error('district') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('resellers.index') }}" class="py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Cancel</a>
                    <button type="submit" class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800 shadow-lg hover:shadow-xl transition-shadow">Save Reseller</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function resellerForm() {
            return {
                country: '{{ old('country', 'Sri Lanka') }}',
                province: '{{ $initialProvince }}',
                district: '{{ old('district') }}',
                city: '{{ old('city') }}',
                provinces: @json($slData),
                availableDistricts: @json($initialDistricts),
                availableCities: [],
                isDynamic: false,

                init() {
                    if (this.country === 'Sri Lanka') {
                         this.fetchCities();
                    }
                },
                
                handleCountryChange() {
                    if (this.country !== 'Sri Lanka') {
                        this.province = '';
                        this.district = '';
                        this.city = '';
                        this.availableDistricts = [];
                        this.availableCities = [];
                        this.isDynamic = true;
                    } else {
                         this.province = '';
                         this.availableDistricts = [];
                         this.isDynamic = true;
                    }
                },

                handleProvinceChange() {
                     this.updateDistricts();
                     this.district = '';
                     this.city = '';
                     this.availableCities = [];
                     this.isDynamic = true;
                },

                handleDistrictChange() {
                     this.fetchCities();
                     this.city = '';
                },

                updateDistricts() {
                    if (this.province && this.provinces[this.province]) {
                        this.availableDistricts = this.provinces[this.province];
                    } else {
                        this.availableDistricts = [];
                    }
                },

                async fetchCities() {
                    if (!this.district) {
                        this.availableCities = [];
                        return;
                    }
                    try {
                        let response = await fetch(`/api/cities?district=${this.district}`);
                        this.availableCities = await response.json();
                    } catch (error) {
                        console.error('Failed to fetch cities:', error);
                    }
                }
            }
        }
    </script>
</x-app-layout>
