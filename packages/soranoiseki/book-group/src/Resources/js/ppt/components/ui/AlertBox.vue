<script setup lang="ts">
import { computed } from 'vue';

// info   = format example (inset sample block)
// danger = notes / pitfalls (amber, bulleted)
// tip    = plain message (blue, free text)
const props = withDefaults(defineProps<{ type?: 'info' | 'danger' | 'tip'; title: string }>(), { type: 'info' });

// Titles were written as "格式范例：" - drop the trailing colon for a cleaner heading.
const heading = computed(() => props.title.replace(/[：:]\s*$/, ''));
</script>

<template>
    <!-- Tip: single-line friendly notice -->
    <div v-if="type === 'tip'" role="note"
        class="flex items-start gap-3 px-4 py-3 text-sm rounded-xl bg-sky-50 text-sky-900 ring-1 ring-sky-200/80 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-800">
        <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-sky-100 text-sky-600 shrink-0 dark:bg-sky-900 dark:text-sky-300">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a7 7 0 0 0-4 12.74V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.26A7 7 0 0 0 12 2Zm-2 18h4v1a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-1Z" />
            </svg>
        </span>
        <div class="min-w-0 pt-0.5 leading-relaxed">
            <span class="font-semibold">{{ heading }}：</span><slot />
        </div>
    </div>

    <!-- Info / danger: card with icon chip and heading -->
    <div v-else role="note" class="rounded-xl ring-1 overflow-hidden"
        :class="type === 'danger'
            ? 'bg-amber-50/70 ring-amber-200 dark:bg-amber-950/30 dark:ring-amber-800'
            : 'bg-white ring-gray-200 dark:bg-gray-800 dark:ring-gray-700'">
        <div class="flex items-center gap-2 px-3.5 pt-3 pb-2">
            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0"
                :class="type === 'danger'
                    ? 'bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-300'
                    : 'bg-primary-100 text-primary-700'">
                <svg v-if="type === 'danger'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2 1 21h22L12 2Zm1 15h-2v-2h2v2Zm0-4h-2V9h2v4Z" />
                </svg>
                <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
                    <path d="M14 3v5h5M9 13h6M9 17h4" />
                </svg>
            </span>
            <span class="text-xs font-bold tracking-wide"
                :class="type === 'danger' ? 'text-amber-800 dark:text-amber-300' : 'text-gray-600 dark:text-gray-300'">
                {{ heading }}
            </span>
        </div>

        <!-- Notes: bulleted list -->
        <ul v-if="type === 'danger'"
            class="px-3.5 pb-3 pl-8 text-[13px] leading-relaxed text-amber-900 dark:text-amber-200 list-disc marker:text-amber-500 [&>li]:py-0.5">
            <slot />
        </ul>

        <!-- Examples: inset sample block -->
        <div v-else class="px-3 pb-3">
            <ul class="px-3 py-2 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300 list-none rounded-lg bg-gray-50 dark:bg-gray-900 [&>li]:py-0.5">
                <slot />
            </ul>
        </div>
    </div>
</template>
