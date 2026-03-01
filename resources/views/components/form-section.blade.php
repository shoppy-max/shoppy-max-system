@props(['title', 'description' => null])
<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-200 dark:border-gray-700']) }}>
    @if($title)
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">{{ $title }}</h3>
            @if($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    @endif
    
    {{ $slot }}
</div>