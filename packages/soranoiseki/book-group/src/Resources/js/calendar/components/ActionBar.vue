<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';

defineProps<{ compact?: boolean }>();

const cal = useCalendarStore();
const { hasCalendar, isGenerating, isLoading, editable } = storeToRefs(cal);
</script>

<template>
    <!-- History versions cannot be generated or downloaded -->
    <div v-if="hasCalendar && editable" class="flex flex-wrap items-center gap-3">
        <span v-if="isGenerating" class="text-xs text-gray-500">正在生成，大约需要十几秒...</span>

        <button type="button" :disabled="isGenerating || isLoading" @click="cal.generatePdf('preview')"
            :class="compact ? 'h-9' : 'h-11'"
            class="inline-flex items-center gap-2 px-5 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 hover:bg-primary-100/60 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            预览
        </button>

        <button type="button" :disabled="isGenerating || isLoading" @click="cal.generatePdf('download')"
            :class="compact ? 'h-9' : 'h-11'"
            class="inline-flex items-center gap-2 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:shadow-none disabled:hover:translate-y-0 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
            <svg v-if="!isGenerating" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
            </svg>
            <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            {{ isGenerating ? '生成中...' : '下载 PDF' }}
        </button>
    </div>
</template>
