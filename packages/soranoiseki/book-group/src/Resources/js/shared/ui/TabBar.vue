<script setup lang="ts">
// Segmented tab bar (same look as the PPT page). `status` per tab:
//   "done"      -> green check
//   "partial"   -> orange badge with `badge` text
//   undefined   -> nothing
export interface TabItem {
    id: string;
    label: string;
    icon: string; // svg path (24x24, stroke style)
    status?: 'done' | 'partial';
    badge?: string;
}

defineProps<{ tabs: TabItem[]; modelValue: string; compact?: boolean }>();
defineEmits<{ (e: 'update:modelValue', id: string): void }>();
</script>

<template>
    <div class="overflow-x-auto">
        <nav class="inline-flex min-w-full sm:min-w-0 gap-1 p-1 rounded-2xl bg-gray-100 dark:bg-gray-900" role="tablist">
            <button v-for="t in tabs" :key="t.id" type="button" role="tab" :aria-selected="modelValue === t.id"
                @click="$emit('update:modelValue', t.id)" :class="[
                    'flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-5 rounded-xl text-sm font-semibold whitespace-nowrap transition-all duration-300 ease-out outline-none focus-visible:ring-2 focus-visible:ring-primary-500',
                    compact ? 'py-1.5' : 'py-2.5',
                    modelValue === t.id
                        ? 'bg-white text-primary-800 shadow-sm dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 hover:text-main hover:bg-white/60 dark:hover:bg-gray-800',
                ]">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path :d="t.icon" />
                </svg>
                {{ t.label }}
                <svg v-if="t.status === 'done'" class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="currentColor"
                    aria-label="已完成">
                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.2 14.4-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4-6.4 6.4Z" />
                </svg>
                <span v-else-if="t.status === 'partial'"
                    class="px-1.5 py-0.5 rounded-full text-[11px] leading-none font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 tabular-nums">
                    {{ t.badge }}
                </span>
            </button>
        </nav>
    </div>
</template>
