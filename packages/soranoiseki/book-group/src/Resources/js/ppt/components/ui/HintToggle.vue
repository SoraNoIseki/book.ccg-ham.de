<script setup lang="ts">
// Small "format help" button that expands/collapses the hint panel below a field.
defineProps<{ open: boolean; controls?: string }>();
defineEmits<{ (e: 'toggle'): void }>();
</script>

<template>
    <button type="button" :aria-expanded="open" :aria-controls="controls" @click="$emit('toggle')"
        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-100/60 dark:text-primary-300 dark:hover:bg-gray-700 transition outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M9.5 9a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1 1-1.1 1.8M12 17h.01" />
        </svg>
        格式说明
        <svg class="w-3 h-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
            aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>
</template>
