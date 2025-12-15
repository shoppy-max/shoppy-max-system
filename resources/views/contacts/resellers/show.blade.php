<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reseller Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Reseller Information</h3>
                        <p class="mt-1 text-sm text-gray-600">Details of the selected reseller.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business Name</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $reseller->business_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Name</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $reseller->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mobile</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $reseller->mobile }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Due Amount</label>
                            <p class="mt-1 text-lg text-gray-900">{{ number_format($reseller->due_amount, 2) }}</p>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <a href="{{ route('resellers.edit', $reseller) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                            Edit
                        </a>
                        <a href="{{ route('resellers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
