<script setup lang="ts">
// "Export as CSV" button of an imported list (same look as the template download button).
defineProps<{ disabled?: boolean }>();
</script>

<template>
    <button type="button" :disabled="disabled"
        class="inline-flex items-center gap-2 whitespace-nowrap h-9 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 transition disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
        </svg>
        <slot />
    </button>
</template>
