<div x-data="{ tab: '{{ $defaultTab }}' }">
    <div class="mb-4 border-b border-gray-700 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
            {{ $labels }}
        </ul>
    </div>
    <div class="mt-8">
        {{ $tabs }}
    </div>
    <div class="mt-5">
        {{ $slot }}
    </div>
</div>