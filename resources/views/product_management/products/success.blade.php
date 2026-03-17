<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6 text-center text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h2 class="mb-2 text-3xl font-bold">Product Created Successfully!</h2>
                    <p class="mb-8 text-lg text-gray-600 dark:text-gray-400">
                        Product <strong class="text-gray-900 dark:text-white">{{ $product->name }}</strong> has been added to the inventory with
                        <strong class="text-gray-900 dark:text-white">{{ $product->variants->count() }}</strong> barcode-ready variant{{ $product->variants->count() === 1 ? '' : 's' }}.
                    </p>

                    <div class="mb-8 grid grid-cols-1 gap-4 text-left sm:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Product</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $product->name }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $product->category?->name ?? '-' }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Variants</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $product->variants->count() }}</p>
                        </div>
                    </div>

                    <h3 class="mb-4 border-b border-gray-200 pb-2 text-left text-xl font-semibold dark:border-gray-700">Generated Variants</h3>

                    <div class="relative mb-8 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Variant</th>
                                    <th class="px-6 py-3">SKU</th>
                                    <th class="px-6 py-3 text-right">Price</th>
                                    <th class="px-6 py-3 text-center">Barcode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->variants as $variant)
                                    <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800 last:border-b-0">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            {{ $variant->unit_value ? $variant->unit_value . ' ' : '' }}{{ $variant->unit->name }}{{ $variant->unit->short_name ? ' (' . $variant->unit->short_name . ')' : '' }}
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs">{{ $variant->sku }}</td>
                                        <td class="px-6 py-4 text-right">Rs. {{ number_format((float) $variant->selling_price, 2) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="{{ route('products.barcode.print', $variant->id) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-xs font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                                Print {{ max((int) $variant->quantity, 1) }} {{ max((int) $variant->quantity, 1) === 1 ? 'Label' : 'Labels' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col justify-center gap-4 sm:flex-row">
                        <a href="{{ route('products.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            Back to Product List
                        </a>
                        <a href="{{ route('products.create') }}" class="rounded-lg bg-green-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700">
                            Add Another Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
