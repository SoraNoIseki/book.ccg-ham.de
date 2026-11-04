<script setup lang="ts">
withDefaults(defineProps<{ type?: 'info' | 'danger'; title: string }>(), { type: 'info' });
</script>

<template>
    <div role="alert" class="flex p-4 text-sm rounded-lg dark:bg-gray-800"
        :class="type === 'danger'
            ? 'text-orange-800 bg-orange-50 dark:text-orange-300'
            : 'text-blue-800 bg-blue-50 dark:text-blue-400'">
        <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
            fill="currentColor" viewBox="0 0 20 20">
            <path
                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
        </svg>
        <div>
            <span class="font-medium">{{ title }}</span>
            <ul class="mt-1.5 list-none list-inside">
                <slot />
            </ul>
        </div>
    </div>
</template>
