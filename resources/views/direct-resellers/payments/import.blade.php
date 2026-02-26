<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Import Payments') }}
        </h2>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800">
        <!-- Step 1: Upload Form -->
        @if(!isset($previewData))
        <div class="max-w-xl mx-auto">
            <div class="mb-6 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Bulk Payment Import</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Download the template, fill in the amounts, and upload it back.</p>
            </div>

            <div class="flex justify-center mb-8">
                <a href="{{ route('direct-reseller-payments.import.template') }}" class="flex items-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Template (Excel)
                </a>
            </div>

            <form action="{{ route('direct-reseller-payments.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
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
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Import Preview</h3>
                <div class="text-sm">
                    <span class="font-bold text-green-600">{{ $validRowsCount }} Valid Rows</span>
                    @if($hasErrors)
                        <span class="ml-4 font-bold text-red-600">Errors Found - Fix file and re-upload</span>
                    @endif
                </div>
            </div>

            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-6">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Direct Reseller</th>
                            <th scope="col" class="px-6 py-3">Current Due</th>
                            <th scope="col" class="px-6 py-3">Payment Amount</th>
                            <th scope="col" class="px-6 py-3">Method</th>
                            <th scope="col" class="px-6 py-3">Reference</th>
                            <th scope="col" class="px-6 py-3">Date</th>
                            <th scope="col" class="px-6 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($previewData as $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 {{ !empty($row['errors']) ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $row['reseller_name'] }} <br>
                                <span class="text-xs text-gray-400">ID: {{ $row['reseller_id'] }}</span>
                            </td>
                            <td class="px-6 py-4">{{ number_format($row['current_due'], 2) }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ number_format($row['amount'], 2) }}</td>
                            <td class="px-6 py-4 uppercase">{{ $row['method'] }}</td>
                            <td class="px-6 py-4">{{ $row['reference'] }}</td>
                            <td class="px-6 py-4">{{ $row['date'] }}</td>
                            <td class="px-6 py-4">
                                @if(empty($row['errors']))
                                    <span class="text-green-600">OK</span>
                                @else
                                    <ul class="text-red-600 list-disc list-inside">
                                        @foreach($row['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center">No payment rows found with Amount > 0.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('direct-reseller-payments.import.show') }}" class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 focus:outline-none focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Cancel / Re-upload</a>
                
                @if(!$hasErrors && $validRowsCount > 0)
                <form action="{{ route('direct-reseller-payments.import.store') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">
                        Confirm Import ({{ $validRowsCount }} Payments)
                    </button>
                </form>
                @else
                <button disabled class="text-white bg-gray-400 cursor-not-allowed font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600">
                    Cannot Import (Fix Errors)
                </button>
                @endif
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
