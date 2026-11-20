<script setup lang="ts">
// Small "edit" button at the end of a table row (same look as the edit button of the library).
defineProps<{ label: string }>();
</script>

<template>
    <button type="button" :aria-label="label"
        class="inline-flex items-center gap-1.5 whitespace-nowrap h-9 px-3 rounded-lg text-sm font-semibold text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40 hover:bg-primary-100/60 dark:hover:bg-gray-700 active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </svg>
        编辑
    </button>
</template>
