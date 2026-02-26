<x-app-layout>
    @php
        $initialProvince = old('province', $reseller->province);
        $initialDistricts = $initialProvince && isset($slData[$initialProvince]) ? $slData[$initialProvince] : [];
    @endphp

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
                            <a href="{{ route('direct-resellers.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Direct Resellers</a>
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
            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Edit Direct Reseller</h2>
        </div>

        <form action="{{ route('direct-resellers.update', $reseller) }}" method="POST" x-data="resellerForm()">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Basic Info Section -->
                    <x-form-section title="Basic Information" description="Update the primary details for this reseller.">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Full Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="name" id="name" value="{{ old('name', $reseller->name) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                </div>
                                @error('name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>
    
                            <!-- Business Name -->
                            <div>
                                <label for="business_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Business Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                             <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4-16v1a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V5H9Z"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $reseller->business_name) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                </div>
                                @error('business_name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>
                            
                             <!-- Initial Due Amount (Locked) -->
                            <div class="md:col-span-2">
                                 <label for="due_amount_display" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Initial Due Amount</label>
                                 <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Rs.</span>
                                    </div>
                                    <input type="number" step="0.01" id="due_amount_display" value="{{ $reseller->due_amount }}" class="bg-gray-100 border border-gray-300 text-gray-700 text-sm rounded-lg block w-full ps-12 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 cursor-not-allowed" readonly disabled>
                                </div>
                                 <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Initial due amount is locked after creation. Ongoing due updates are handled by order/payment flows.</p>
                            </div>

                        </div>
                    </x-form-section>
    
                    <!-- Address Section -->
                    <x-form-section title="Location Details" description="Address and location information.">
                         <div class="space-y-4">
                            <!-- Address -->
                            <div>
                                <label for="address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Street Address <span class="text-red-500">*</span></label>
                                <textarea name="address" id="address" rows="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>{{ old('address', $reseller->address) }}</textarea>
                                @error('address') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>
                            
                            <!-- Location Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="country" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Country <span class="text-red-500">*</span></label>
                                    <select name="country" id="country" x-model="country" @change="handleCountryChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        @foreach(config('locations.countries') as $c)
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endforeach
                                    </select>
                                    @error('country') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                                </div>
    
                                <div>
                                    <label for="city" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">City <span class="text-red-500">*</span></label>
                                      <!-- Dynamic Dropdown for Sri Lanka -->
                                    <div x-show="country === 'Sri Lanka'">
                                        <select name="city" id="city_select" x-model="city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" :required="country === 'Sri Lanka'">
                                            <option value="">Select City</option>
                                            <template x-for="c in availableCities" :key="c.city_name">
                                                <option :value="c.city_name" x-text="c.city_name" :selected="c.city_name === city"></option>
                                            </template>
                                        </select>
                                    </div>
        
                                    <!-- Standard Input for Other Countries -->
                                    <div x-show="country !== 'Sri Lanka'">
                                        <input type="text" name="city" id="city_input" x-model="city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Enter City" :required="country !== 'Sri Lanka'">
                                    </div>
                                    @error('city') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
    
                            <!-- Sri Lanka Specific Fields -->
                            <div x-show="country === 'Sri Lanka'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Province -->
                                <div>
                                    <label for="province" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Province</label>
                                    <select name="province" id="province" x-model="province" @change="handleProvinceChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                                        <select name="district" id="district" x-model="district" @change="handleDistrictChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select District</option>
                                            @foreach($initialDistricts as $dist)
                                                <option value="{{ $dist }}" {{ old('district', $reseller->district) == $dist ? 'selected' : '' }}>{{ $dist }}</option>
                                            @endforeach
                                        </select>
                                    </template>
        
                                    <!-- Dynamic Select for Updates (Alpine) -->
                                    <template x-if="isDynamic">
                                        <select name="district" id="district_dynamic" x-model="district" @change="handleDistrictChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                    </x-form-section>

                    @include('contacts.partials.courier-allowlist', [
                        'couriers' => $couriers,
                        'selectedCouriers' => old('couriers', $reseller->couriers->pluck('id')->all()),
                        'entityLabel' => 'direct reseller'
                    ])
                </div>
                
                <!-- Right Column (1/3) -->
                <div class="space-y-6">
                     <!-- Contact Info Section -->
                    <x-form-section title="Contact Details">
                        <div class="space-y-4">
                            <!-- Mobile -->
                             <div>
                                <label for="mobile" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mobile <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $reseller->mobile) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                </div>
                                @error('mobile') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>
                            
                            <!-- Landline -->
                            <div>
                                <label for="landline" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Landline</label>
                                 <input type="text" name="landline" id="landline" value="{{ old('landline', $reseller->landline) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                 @error('landline') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>
    
                            <!-- Email -->
                             <div>
                                <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                                 <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                       <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16">
                                            <path d="m10.036 8.278 9.258-7.79A1.979 1.979 0 0 0 18 0H2A1.987 1.987 0 0 0 .641.541l9.395 7.737Z"/>
                                            <path d="M11.241 9.817c-.36.275-.801.425-1.255.427-.428 0-.845-.138-1.187-.395L0 2.6V14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2.5l-8.759 7.317Z"/>
                                        </svg>
                                    </div>
                                    <input type="email" name="email" id="email" value="{{ old('email', $reseller->email) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                                 @error('email') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </x-form-section>
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col gap-4">
                        <button type="submit" class="w-full text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:focus:ring-yellow-900 shadow-lg hover:shadow-xl transition-shadow flex justify-center items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Update Direct Reseller
                        </button>
                        <a href="{{ route('direct-resellers.index') }}" class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 text-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-layout>

    <script>
        function resellerForm() {
            return {
                country: '{{ old('country', $reseller->country ?? 'Sri Lanka') }}',
                province: '{{ $initialProvince }}',
                district: '{{ old('district', $reseller->district) }}',
                city: '{{ old('city', $reseller->city) }}',
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
                         this.isDynamic = true; // Switch to dynamic when clearing
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
