<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pack Order') }} #{{ $order->order_number }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="packer()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex gap-6">
                <!-- Scanner Section -->
                <div class="w-full md:w-2/3 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <label class="block text-gray-700 text-lg font-bold mb-2">Scan Barcode / SKU</label>
                        <input type="text" x-ref="scanInput" x-model="scanInput" @keydown.enter.prevent="scanItem()" autofocus class="shadow border-2 border-indigo-500 rounded w-full py-4 px-4 text-gray-700 text-xl leading-tight focus:outline-none focus:shadow-outline" placeholder="Scan item barcode here...">
                        <p class="text-sm text-gray-500 mt-2">First valid scan moves the order to Picked From Rack automatically. Press Enter after scanning or typing SKU.</p>
                    </div>

                    <h3 class="text-lg font-bold mb-4">Items to Pack</h3>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                        <div class="flex justify-between items-center p-4 border rounded" 
                             :class="isPacked('{{ $item->sku }}') ? 'bg-green-100 border-green-500' : 'bg-gray-50'">
                            <div>
                                <p class="font-bold text-lg">{{ $item->product_name }}</p>
                                <p class="text-sm text-gray-600">SKU: {{ $item->sku }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold">Qty: {{ $item->quantity }}</p>
                                <span x-show="isPacked('{{ $item->sku }}')" class="text-green-600 font-bold">PACKED</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Action Section -->
                <div class="w-full md:w-1/3 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col justify-between">
                    <div>
                        <h4 class="text-xl font-bold mb-4">Status</h4>
                        <div class="mb-4">
                            <p><strong>Customer:</strong> {{ $order->customer_name }}</p>
                            <p><strong>Waybill:</strong> {{ $order->waybill_number ?? 'N/A' }}</p>
                        </div>
                        
                        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700" x-text="statusText"></div>

                        <div class="p-4 rounded text-center mb-6 transition-colors" :class="allPacked ? 'bg-green-500 text-white' : 'bg-yellow-100 text-yellow-800'">
                            <span x-show="!allPacked" class="font-bold text-xl">Scanning...</span>
                            <span x-show="allPacked" class="font-bold text-xl">ALL ITEMS PACKED!</span>
                        </div>
                    </div>

                    <form action="{{ route('orders.packing.mark-packed', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" :disabled="!allPacked" class="w-full bg-indigo-600 text-white font-bold py-4 px-6 rounded text-xl shadow hover:bg-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Complete Packing
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function packer() {
            return {
                scanInput: '',
                currentStatus: @json((string) ($order->delivery_status ?? 'waybill_printed')),
                markingPicked: false,
                packedItems: [], // List of SKUs that have been scanned (simplified logic)
                items: @json($order->items->map(function($item){ return ['sku' => $item->sku, 'qty' => $item->quantity]; })),

                get statusText() {
                    const map = {
                        waybill_printed: 'Current step: Waybill Printed. Start scanning to move this order into Picked From Rack.',
                        picked_from_rack: 'Current step: Picked From Rack. Complete all scans, then mark the order as Packed.',
                        packed: 'Current step: Packed. Return to the queue and mark it as Dispatched.',
                    };

                    return map[this.currentStatus] || 'Current step: Processing fulfillment.';
                },

                notify(type, message) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: type,
                            text: message,
                            timer: 2200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        return;
                    }
                    alert(message);
                },

                async ensurePickedFromRack() {
                    if (this.currentStatus !== 'waybill_printed' || this.markingPicked) {
                        return true;
                    }

                    this.markingPicked = true;

                    try {
                        const response = await fetch(@json(route('orders.packing.mark-picked', $order->id)), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({}),
                        });

                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            this.notify('error', data.message || 'Failed to move order to Picked From Rack.');
                            return false;
                        }

                        this.currentStatus = data.delivery_status || 'picked_from_rack';
                        return true;
                    } catch (error) {
                        this.notify('error', 'Failed to update picking status.');
                        return false;
                    } finally {
                        this.markingPicked = false;
                    }
                },
                
                async scanItem() {
                    const sku = this.scanInput.trim();
                    if (!sku) return;

                    // Find item in order
                    const item = this.items.find(i => i.sku === sku);
                    
                    if (item) {
                        const pickedReady = await this.ensurePickedFromRack();
                        if (!pickedReady) {
                            this.scanInput = '';
                            this.$nextTick(() => this.$refs.scanInput?.focus());
                            return;
                        }

                        // Check if already packed or handle qty decrement logic (simplified here to toggle packed)
                        if (!this.packedItems.includes(sku)) {
                            this.packedItems.push(sku);
                            this.notify('success', `${sku} matched.`);
                        }
                    } else {
                        this.notify('error', 'Wrong item. SKU not found in this order.');
                    }
                    
                    this.scanInput = ''; // Clear input for next scan
                    this.$nextTick(() => this.$refs.scanInput?.focus());
                },
                
                isPacked(sku) {
                    return this.packedItems.includes(sku);
                },
                
                get allPacked() {
                    // Check if all unique SKUs are in packedItems list
                    // Real verification should check quantities too
                    return this.items.every(i => this.packedItems.includes(i.sku));
                }
            }
        }
    </script>
</x-app-layout>
