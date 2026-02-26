<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Import Products') }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('products.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Products</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Import</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800">
        <!-- Step 1: Upload Form -->
        @if(!isset($previewData))
        <div class="max-w-xl mx-auto">
            <div class="mb-6 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Bulk Product Import</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Download the template, fill in your product details, and upload it back.</p>
                <div class="mt-2 text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded">
                    <strong>Note:</strong> If you use the same "Product Name" for multiple rows, they will be treated as variants of the same product.
                </div>
                <div class="mt-2 text-xs text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 p-2 rounded">
                    <strong>SKU:</strong> SKU is auto-generated during import and enforced as unique.
                </div>
                <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/20 p-2 rounded">
                    <strong>Stock:</strong> Imported products always start with stock <strong>0</strong>. Stock is updated from purchases and order flows.
                </div>
            </div>

            <div class="flex justify-center mb-8">
                <a href="{{ route('products.import.template') }}" class="flex items-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Template (Excel)
                </a>
            </div>

            <form action="{{ route('products.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="file_input">Upload Filled Excel File</label>
                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="file_input" name="file" type="file" required accept=".xlsx,.xls,.csv">
                </div>
                <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800">
                    Preview & Validate
                </button>
            </form>
        </div>
        @endif

        <!-- Step 2: Preview -->
        @if(isset($previewData))
        <div x-data="importManager()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Import Preview</h3>
                <div class="text-sm">
                    <span class="font-bold text-green-600">{{ $validRowsCount }} Valid Rows</span>
                    @if($hasErrors)
                        <span class="ml-4 font-bold text-red-600">Errors Found - Fix file or use Quick Create</span>
                    @endif
                </div>
            </div>

            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-6">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Product Name</th>
                            <th scope="col" class="px-6 py-3">Category</th>
                            <th scope="col" class="px-6 py-3">Variant (Unit)</th>
                            <th scope="col" class="px-6 py-3">SKU (Auto)</th>
                            <th scope="col" class="px-6 py-3">Price</th>
                            <th scope="col" class="px-6 py-3">Stock</th>
                            <th scope="col" class="px-6 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $currentProductName = null;
                        @endphp
                        @forelse($previewData as $index => $row)
                        @php
                            $isNewProduct = $row['name'] !== $currentProductName;
                            $currentProductName = $row['name'];
                        @endphp
                        
                        <!-- Product Group Header if New Product -->
                        @if($isNewProduct)
                            <tr class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                                <td colspan="7" class="px-6 py-2 font-bold text-gray-800 dark:text-gray-200">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($row['image_url']))
                                            <img src="{{ $row['image_url'] }}" class="w-8 h-8 rounded object-cover border border-gray-300 bg-white" alt="img" onerror="this.onerror=null;this.src='';this.style.display='none'">
                                        @endif
                                        <span>{{ $row['name'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ !empty($row['errors']) ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-6 py-4 pl-12">
                                <span class="text-xs text-gray-400">{{ $isNewProduct ? 'Primary Variant' : 'Variant' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-500">Cat:</span>
                                        @if(isset($row['errors']['category']) && $row['errors']['category'] === 'MISSING_CATEGORY')
                                            <button @click="confirmCreate('category', '{{ $row['category_name'] }}')" type="button" class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded hover:bg-blue-700 transition">
                                                Create "{{ $row['category_name'] }}"
                                            </button>
                                        @else
                                            <span class="{{ isset($row['errors']['category']) ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $row['category_name'] }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($row['sub_category_name'])
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-500">Sub:</span>
                                        @if(isset($row['errors']['sub_category']) && $row['errors']['sub_category'] === 'MISSING_SUB_CATEGORY')
                                            @if(!isset($row['errors']['category'])) {{-- Can only create sub if cat exists --}}
                                                <button @click="confirmCreate('sub_category', '{{ $row['sub_category_name'] }}', {{ $row['category_id'] }})" type="button" class="text-xs bg-purple-600 text-white px-2 py-0.5 rounded hover:bg-purple-700 transition">
                                                    Create "{{ $row['sub_category_name'] }}"
                                                </button>
                                            @else
                                                <span class="text-xs text-red-400" title="Fix Category first">Must create Category first</span>
                                            @endif
                                        @else
                                             <span class="{{ isset($row['errors']['sub_category']) ? 'text-red-500' : 'text-gray-600 dark:text-gray-400' }}">
                                                {{ $row['sub_category_name'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                {{ $row['unit_value'] }} {{ $row['unit_name'] }}
                                @if(isset($row['errors']['unit']) && $row['errors']['unit'] === 'MISSING_UNIT')
                                    <button @click="confirmCreate('unit', '{{ $row['unit_name'] }}')" type="button" class="ml-2 text-xs bg-green-600 text-white px-2 py-0.5 rounded hover:bg-green-700 transition">
                                        Create Unit
                                    </button>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">{{ $row['sku'] }}</td>
                            <td class="px-6 py-4">{{ number_format($row['selling_price'], 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">0 (Auto)</span>
                            </td>
                            <td class="px-6 py-4">
                                @if(empty($row['errors']))
                                    <span class="text-green-600 font-bold">OK</span>
                                @else
                                    <ul class="text-red-600 list-disc list-inside text-xs">
                                        @foreach($row['errors'] as $key => $error)
                                            @if(!in_array($error, ['MISSING_CATEGORY', 'MISSING_SUB_CATEGORY', 'MISSING_UNIT', 'Required']))
                                                <li>{{ $error }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center">No valid rows found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('products.import.show') }}" class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 focus:outline-none focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Cancel / Re-upload</a>
                
                @if(!$hasErrors && $validRowsCount > 0)
                <form action="{{ route('products.import.store') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">
                        Confirm Import ({{ $validRowsCount }} Rows)
                    </button>
                </form>
                @else
                <button disabled class="text-white bg-gray-400 cursor-not-allowed font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600">
                    Fix Errors to Import
                </button>
                @endif
            </div>

            <!-- Custom Confirmation Modal -->
            <div x-show="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-96 transform transition-all" @click.away="showConfirmModal = false">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Quick Create</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6" x-text="confirmMessage"></p>
                    <div class="flex justify-end gap-3">
                        <button @click="showConfirmModal = false" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancel</button>
                        <button @click="performCreate" class="px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700">Yes, Create</button>
                    </div>
                </div>
            </div>

            <!-- Toast Notification -->
            <div x-show="toast.visible" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded shadow-lg text-white text-sm font-medium"
                 :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
                 style="display: none;">
                <span x-text="toast.message"></span>
            </div>

        </div>
        
        <script>
            function importManager() {
                return {
                    showConfirmModal: false,
                    confirmMessage: '',
                    pendingAction: null,
                    toast: { visible: false, message: '', type: 'success' },

                    confirmCreate(type, name, parentId = null) {
                        this.pendingAction = { type, name, parentId };
                        this.confirmMessage = `Are you sure you want to create the ${type.replace('_', ' ')} "${name}"?`;
                        this.showConfirmModal = true;
                    },

                    async performCreate() {
                        this.showConfirmModal = false;
                        const { type, name, parentId } = this.pendingAction;
                        
                        let url = '';
                        let data = {};

                        if (type === 'category') {
                            url = "{{ route('quick.category.store') }}"; 
                            data = { name: name };
                        } else if (type === 'sub_category') {
                             url = "{{ route('quick.subcategory.store') }}";
                             data = { name: name, category_id: parentId };
                        } else if (type === 'unit') {
                            url = "{{ route('quick.unit.store') }}";
                            data = { name: name, short_name: name.substring(0,3).toUpperCase() }; 
                        }

                        try {
                            const response = await axios.post(url, data);
                            if (response.status === 200 || response.status === 201) {
                                this.showToast(`${name} created successfully! Refreshing...`, 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        } catch (error) {
                            console.error(error);
                            this.showToast('Failed to create. ' + (error.response?.data?.message || 'Unknown error'), 'error');
                        }
                    },

                    showToast(message, type = 'success') {
                        this.toast.message = message;
                        this.toast.type = type;
                        this.toast.visible = true;
                        setTimeout(() => { this.toast.visible = false }, 3000);
                    }
                }
            }
        </script>
        @endif
    </div>
</x-app-layout>
