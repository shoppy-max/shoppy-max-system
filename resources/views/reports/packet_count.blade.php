<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Pack Count Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <h3 class="font-bold mb-4">Packing Performance</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($packers as $packer)
                            <div class="bg-gray-50 p-6 rounded-lg border shadow-sm flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-lg">{{ $packer->name }}</p>
                                    <p class="text-xs text-gray-500 uppercase">{{ $packer->role_name ?? 'Staff' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="block text-4xl font-bold text-blue-600">{{ $packer->packed_orders_count }}</span>
                                    <span class="text-xs text-gray-500 uppercase">Orders Packed</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
