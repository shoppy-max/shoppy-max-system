<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Receive Courier Payment') }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Receive Courier Payment</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Courier</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Choose an active courier to start receiving and reconciling payment records.</p>
            </div>
            <div class="w-full lg:w-80">
                <label for="courier_search" class="sr-only">Search Courier</label>
                <input
                    id="courier_search"
                    type="text"
                    oninput="filterCourierCards(this.value)"
                    placeholder="Search courier by name..."
                    class="block w-full p-2.5 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                >
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" id="courier-card-grid">
            @forelse($couriers as $courier)
                <a href="{{ route('courier-receive.show', $courier->id) }}" class="courier-card block p-5 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-primary-300 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-700 transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $courier->name }}</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $courier->phone ?: 'No phone available' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $courier->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $courier->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="mt-4 text-xs text-primary-700 dark:text-primary-400 font-medium">Open receive screen</p>
                </a>
            @empty
                <div class="col-span-full p-10 text-center text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 rounded-lg dark:border-gray-600">
                    No active couriers found.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function filterCourierCards(searchValue) {
            const query = (searchValue || '').toLowerCase().trim();
            const cards = document.querySelectorAll('.courier-card');

            cards.forEach((card) => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
