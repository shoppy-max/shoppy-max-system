<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Packing & Dispatch') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <h3 class="text-lg font-medium mb-4">Fulfillment Queue</h3>
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waybill</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($orders as $order)
                                @php
                                    $deliveryStatus = strtolower((string) ($order->delivery_status ?? 'pending'));
                                    $deliveryLabels = [
                                        'waybill_printed' => 'Waybill Printed',
                                        'picked_from_rack' => 'Picked From Rack',
                                        'packed' => 'Packed',
                                    ];
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 font-bold">{{ $order->order_number }}</td>
                                    <td class="px-6 py-4 font-mono">{{ $order->waybill_number ?? 'Not Generated' }}</td>
                                    <td class="px-6 py-4">{{ $order->items->count() }} items</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $deliveryStatus === 'packed' ? 'bg-blue-100 text-blue-800' : ($deliveryStatus === 'picked_from_rack' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800') }}">
                                            {{ $deliveryLabels[$deliveryStatus] ?? ucfirst(str_replace('_', ' ', $deliveryStatus)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($deliveryStatus === 'packed')
                                            <form action="{{ route('orders.packing.mark-dispatched', $order->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-cyan-600 hover:bg-cyan-800 text-white font-bold py-2 px-4 rounded">
                                                    Mark Dispatched
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('orders.packing.process', $order->id) }}" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded">
                                                {{ $deliveryStatus === 'picked_from_rack' ? 'Continue Packing' : 'Start Picking' }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No orders ready for picking, packing, or dispatch.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
