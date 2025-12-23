<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Wise Sales Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User / Reseller</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Revenue (Delivered)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders Delivered</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($userSales as $user)
                                @if($user->orders_sum_total_amount > 0)
                                <tr>
                                    <td class="px-6 py-4 font-bold">{{ $user->name }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-500">{{ $user->user_type }}</td>
                                    <td class="px-6 py-4 text-green-600 font-bold">{{ number_format($user->orders_sum_total_amount, 2) }}</td>
                                    <td class="px-6 py-4">{{ $user->orders_count }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
