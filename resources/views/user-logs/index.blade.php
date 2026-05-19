<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('User Logs') }}
            </h2>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">User Logs</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    @php
        $jsonBlock = function ($value) {
            if ($value === null || $value === [] || $value === '') {
                return null;
            }

            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        };

        $statusBadge = function ($status) {
            if ($status === null) {
                return ['Not set', 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600'];
            }

            return $status >= 400
                ? [$status, 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800']
                : [$status, 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800'];
        };
    @endphp

    <div class="p-6 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Logs</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-900/20">
                <p class="text-xs font-medium uppercase text-green-700 dark:text-green-300">Successful</p>
                <p class="mt-2 text-2xl font-semibold text-green-800 dark:text-green-200">{{ number_format($summary['success']) }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20">
                <p class="text-xs font-medium uppercase text-red-700 dark:text-red-300">Failed</p>
                <p class="mt-2 text-2xl font-semibold text-red-800 dark:text-red-200">{{ number_format($summary['failed']) }}</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
                <p class="text-xs font-medium uppercase text-blue-700 dark:text-blue-300">Users</p>
                <p class="mt-2 text-2xl font-semibold text-blue-800 dark:text-blue-200">{{ number_format($summary['users']) }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('user-logs.index') }}" class="mb-6">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-3 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 1 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="search" name="search" value="{{ request('search') }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Search user, operation, page, subject..." />
                </div>

                <select name="module" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white lg:col-span-2">
                    <option value="">All modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>

                <select name="action" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white lg:col-span-2">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ \App\Models\UserLog::labelForAction($action) }}</option>
                    @endforeach
                </select>

                <select name="user_id" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white lg:col-span-2">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>

                <select name="status" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white lg:col-span-1">
                    <option value="">Any status</option>
                    <option value="success" @selected(request('status') === 'success')>Success</option>
                    <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                </select>

                <div class="flex items-center gap-2 lg:col-span-2">
                    <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Filter</button>
                    @if(request()->query())
                        <a href="{{ route('user-logs.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Clear</a>
                    @endif
                </div>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From ({{ config('app.timezone') }})</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To ({{ config('app.timezone') }})</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </label>
                <select name="method" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Any method</option>
                    @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
                <input type="text" name="route_name" value="{{ request('route_name') }}" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Page/action..." />
                <input type="number" name="status_code" value="{{ request('status_code') }}" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Exact status..." />
                <input type="text" name="ip_address" value="{{ request('ip_address') }}" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="IP address..." />
                <select name="auditable_type" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Any subject</option>
                    @foreach($subjectTypes as $subjectType)
                        <option value="{{ $subjectType }}" @selected(request('auditable_type') === $subjectType)>{{ class_basename($subjectType) }}</option>
                    @endforeach
                </select>
                <input type="number" name="auditable_id" value="{{ request('auditable_id') }}" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Subject ID..." />
                <select name="data_presence" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Any data</option>
                    <option value="changes" @selected(request('data_presence') === 'changes')>Has changes</option>
                    <option value="request" @selected(request('data_presence') === 'request')>Has request data</option>
                </select>
            </div>
        </form>

        <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('user-logs.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                Export Excel
            </a>
            <a href="{{ route('user-logs.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                Export PDF
            </a>
        </div>

        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3">Date & Time ({{ config('app.timezone') }})</th>
                        <th scope="col" class="px-4 py-3">User</th>
                        <th scope="col" class="px-4 py-3">Operation</th>
                        <th scope="col" class="px-4 py-3">Subject</th>
                        <th scope="col" class="px-4 py-3">Page / Area</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php([$statusText, $statusClass] = $statusBadge($log->status_code))
                        <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                            <td class="px-4 py-4 align-top text-gray-900 dark:text-white">
                                <div class="font-medium">{{ $log->occurred_at?->timezone(config('app.timezone'))->format('Y-m-d') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->occurred_at?->timezone(config('app.timezone'))->format('h:i:s A') }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $log->user_name ?? 'System / Guest' }}</div>
                                <div class="max-w-48 truncate text-xs">{{ $log->user_email ?? 'No user attached' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex max-w-64 rounded border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-medium leading-5 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $log->operation_label }}
                                </span>
                                <div class="mt-1 text-xs">{{ $log->action_label }} · {{ $log->module ?? 'System' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $log->subject_label }}</div>
                                @if($log->auditable_type)
                                    <div class="text-xs">{{ $log->technical_subject_label }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $log->location_label }}</div>
                                <div class="mt-1 max-w-64 truncate text-xs">{{ $log->method }} {{ $log->url }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex rounded border px-2 py-1 text-xs font-medium {{ $statusClass }}">{{ $statusText }}</span>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <details class="group">
                                    <summary class="cursor-pointer whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">View Details</summary>
                                    <div class="mt-3 w-[36rem] max-w-[80vw] space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                        <div>
                                            <p class="mb-1 text-xs font-semibold uppercase text-gray-500">Clear Activity</p>
                                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $log->human_description }}</p>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <p class="mb-1 text-xs font-semibold uppercase text-gray-500">Page / Area</p>
                                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $log->location_label }}</p>
                                            </div>
                                            <div>
                                                <p class="mb-1 text-xs font-semibold uppercase text-gray-500">Technical Route</p>
                                                <p class="break-all text-sm text-gray-900 dark:text-gray-100">{{ $log->route_name ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <p class="mb-1 text-xs font-semibold uppercase text-gray-500">IP Address</p>
                                                <p class="break-all text-sm text-gray-900 dark:text-gray-100">{{ $log->ip_address ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="mb-1 text-xs font-semibold uppercase text-gray-500">User Agent</p>
                                                <p class="break-all text-sm text-gray-900 dark:text-gray-100">{{ $log->user_agent ?? '-' }}</p>
                                            </div>
                                        </div>
                                        @foreach(['Request' => $log->readable_request_data, 'Old Values' => $log->readable_old_values, 'New Values' => $log->readable_new_values, 'Metadata' => $log->readable_metadata] as $title => $payload)
                                            @if($json = $jsonBlock($payload))
                                                <div>
                                                    <p class="mb-1 text-xs font-semibold uppercase text-gray-500">{{ $title }}</p>
                                                    <pre class="max-h-64 overflow-auto whitespace-pre-wrap rounded-md bg-white p-3 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">{{ $json }}</pre>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-gray-800">
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                No user logs found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
