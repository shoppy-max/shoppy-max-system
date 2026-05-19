<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Reports</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Operational reports with filtered PDF and Excel downloads.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                    <p class="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">Available PCS</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['stock_pcs']) }}</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">Stock Value</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['stock_value'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-purple-100 bg-purple-50 p-4 dark:border-purple-900 dark:bg-purple-950/30">
                    <p class="text-xs font-semibold uppercase text-purple-700 dark:text-purple-300">Packed Orders</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['packed_orders']) }}</p>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                    <p class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">Returns</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['returned_orders']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach($cards as $card)
                    <a href="{{ $card['route'] }}" class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-300">{{ $card['title'] }}</h3>
                                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $card['description'] }}</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Open</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
