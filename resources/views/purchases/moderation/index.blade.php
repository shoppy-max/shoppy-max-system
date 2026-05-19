<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{{ $stageConfig['title'] }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stageConfig['description'] }}</p>
            </div>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="me-2.5 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <a href="{{ route('purchases.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">Purchases</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="mx-1 h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stageConfig['title'] }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="space-y-6 p-6">
        <div class="rounded-md border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                <a
                    href="{{ route('purchases.moderation.checking') }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-3 text-sm font-medium transition {{ $stage === 'checking' ? 'bg-blue-700 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                >
                    Purchase Checking
                </a>
                <a
                    href="{{ route('purchases.moderation.verifying') }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-3 text-sm font-medium transition {{ $stage === 'verifying' ? 'bg-blue-700 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                >
                    Purchase Verification
                </a>
                @can('view store placement')
                    <a
                        href="{{ route('purchases.moderation.grn') }}"
                        class="inline-flex items-center justify-center rounded-lg px-4 py-3 text-sm font-medium transition {{ $stage === 'grn' ? 'bg-blue-700 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        Store Placement
                    </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                {{ session('info') }}
            </div>
        @endif

        <div class="rounded-md border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ route('purchases.moderation.' . $stage) }}" class="space-y-3">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="relative xl:col-span-8">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/></svg>
                        </div>
                        <input type="search" name="search" value="{{ request('search') }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Search by supplier name or purchasing ID">
                    </div>

                    <div class="xl:col-span-2">
                        <input type="date" name="date" value="{{ request('date') }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="flex items-center gap-2 xl:col-span-2 xl:justify-end">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Apply
                        </button>
                        @if(request()->filled('search') || request()->filled('date'))
                            <a href="{{ route('purchases.moderation.' . $stage) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Purchasing ID</th>
                        <th class="px-6 py-3">Supplier</th>
                        <th class="px-6 py-3 text-right">{{ $stageConfig['final_stage'] ? 'Item (PCS Quantity)' : 'Item Qty (PCS Quantity)' }}</th>
                        @unless($stageConfig['final_stage'])
                            <th class="px-6 py-3 text-right">Net Total</th>
                            <th class="px-6 py-3 text-right">Paid</th>
                            <th class="px-6 py-3 text-right">Balance</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        @endunless
                        <th class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusStyles = [
                            'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                            'checking' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
                            'verified' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'complete' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        ];
                    @endphp
                    @forelse ($purchases as $purchase)
                        @php
                            $balance = (float) $purchase->net_total - (float) $purchase->paid_amount;
                            $isComplete = ($purchase->status ?? 'pending') === 'complete';
                            $hasReceivedUnits = (int) ($purchase->grn_progress_units_count ?? 0) > 0;
                            $actionsLocked = $isComplete || $hasReceivedUnits;
                            $viewRoute = $stage === 'grn'
                                ? route('purchases.store-placement.index', 'retail')
                                : route('purchases.show', $purchase);
                        @endphp
                        <tr class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4">{{ optional($purchase->purchase_date)->format('d M Y') }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $purchase->purchase_number }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $purchase->supplier->business_name ?? $purchase->supplier->name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->supplier->mobile ?? $purchase->supplier->phone ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ number_format((float) ($purchase->total_item_quantity ?? 0), 0) }} pcs</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $purchase->items_count }} item{{ $purchase->items_count === 1 ? '' : 's' }}
                                    @if($stage === 'grn')
                                        •
                                        @if($isComplete)
                                            GRN complete
                                        @else
                                            {{ number_format((int) ($purchase->grn_progress_units_count ?? 0), 0) }} scanned • {{ number_format((int) ($purchase->pending_units_count ?? 0), 0) }} remaining
                                        @endif
                                    @endif
                                </div>
                            </td>
                            @unless($stageConfig['final_stage'])
                                <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format((float) $purchase->net_total, 2) }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $purchase->paid_amount, 2) }}</td>
                                <td class="px-6 py-4 text-right {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ number_format(max($balance, 0), 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="rounded px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$purchase->status ?? 'pending'] ?? $statusStyles['pending'] }}">
                                        {{ ucfirst($purchase->status ?? 'pending') }}
                                    </span>
                                </td>
                            @endunless
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($stage === 'grn')
                                        @can('view store placement')
                                            <a href="{{ $viewRoute }}" class="rounded-lg p-2 text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-gray-700" title="Add to Store">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                        @endcan
                                    @else
                                        <a href="{{ $viewRoute }}" class="rounded-lg p-2 text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-gray-700" title="View">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                    @endif
                                    @can('edit purchases')
                                        @if(!$actionsLocked)
                                            <a href="{{ route('purchases.edit', $purchase) }}" class="rounded-lg p-2 text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-gray-700" title="Edit">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>
                                        @else
                                            <span class="rounded-lg p-2 text-gray-400 dark:text-gray-500" title="{{ $isComplete ? 'Editing locked after completion' : 'Editing locked after store placement starts' }}">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 016 0v2H9z"></path></svg>
                                            </span>
                                        @endif
                                    @endcan
                                    @can('delete purchases')
                                        @if(!$actionsLocked)
                                            <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="inline" data-confirm-message="Delete this purchase?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-gray-700" title="Delete">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="rounded-lg p-2 text-gray-400 dark:text-gray-500" title="{{ $isComplete ? 'Deletion locked after completion' : 'Deletion locked after store placement starts' }}">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 016 0v2H9z"></path></svg>
                                            </span>
                                        @endif
                                    @endcan
                                    @if(!$stageConfig['final_stage'] && !$isComplete)
                                        @can('approve purchases')
                                            <form action="{{ route('purchases.moderation.approve', $purchase) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="stage" value="{{ $stage }}">
                                                <button type="submit" class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-xs font-medium text-white hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700" title="{{ $stageConfig['action_label'] }}">
                                                    {{ $stageConfig['action_label'] }}
                                                </button>
                                            </form>
                                        @endcan
                                    @elseif($stageConfig['final_stage'] && $isComplete)
                                        <span class="inline-flex items-center rounded-lg bg-green-100 px-3 py-2 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            {{ $stageConfig['completed_label'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $stageConfig['final_stage'] ? 5 : 9 }}" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                No purchases found in this moderation queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $purchases->links() }}
        </div>
    </div>
</x-app-layout>
