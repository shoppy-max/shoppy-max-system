@php
    $permissionGroups = $permissionGroups ?? [];
    $selected = $selected ?? [];
    $inputName = $inputName ?? 'permissions[]';
    $selectedPermissions = collect(old('permissions', $selected))->filter()->values()->all();
@endphp

<div class="space-y-4" x-data="{ permissionSearch: '' }">
    <div class="max-w-xl">
        <label for="permission_search" class="sr-only">Search permissions</label>
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"></path></svg>
            </div>
            <input
                id="permission_search"
                type="search"
                x-model.debounce.150ms="permissionSearch"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                placeholder="Search permission, group, or action..."
            >
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        @foreach($permissionGroups as $group)
            @php($groupPermissions = collect($group['permissions'])->filter(fn ($permission) => $permission['id'])->values())
            @continue($groupPermissions->isEmpty())
            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                data-permission-group="{{ $group['key'] }}"
                x-show="permissionSearch === '' || $el.textContent.toLowerCase().includes(permissionSearch.toLowerCase())"
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $group['label'] }}</h4>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $groupPermissions->count() }} permissions</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($groupPermissions as $permission)
                        @php($checked = in_array($permission['name'], $selectedPermissions, true))
                        <label
                            for="perm_{{ $permission['id'] }}"
                            class="flex cursor-pointer items-start gap-3 rounded-md border border-gray-200 p-3 text-sm transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                        >
                            <input
                                id="perm_{{ $permission['id'] }}"
                                name="{{ $inputName }}"
                                value="{{ $permission['name'] }}"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                {{ $checked ? 'checked' : '' }}
                            >
                            <span class="min-w-0">
                                <span class="block font-medium text-gray-800 dark:text-gray-200">{{ $permission['label'] }}</span>
                                <span class="block break-words text-xs text-gray-500 dark:text-gray-400">{{ $permission['name'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
