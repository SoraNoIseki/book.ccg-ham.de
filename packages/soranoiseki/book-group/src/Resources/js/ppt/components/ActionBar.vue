<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../store/powerpoint';

defineProps<{ compact?: boolean }>();

const ppt = usePowerpointStore();
const { saveEnabled, isSubmitting, isLoading, canDownload } = storeToRefs(ppt);
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <template v-if="saveEnabled">
            <button type="button" :disabled="isSubmitting || isLoading" @click="ppt.save()"
                :class="compact ? 'h-9' : 'h-11'"
                class="inline-flex items-center gap-2 px-5 rounded-xl text-sm font-semibold ease-out text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed focus-visible:ring-2 focus-visible:ring-primary-600 outline-none">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" />
                    <path d="M17 21v-8H7v8M7 3v5h8" />
                </svg>
                保存
            </button>

            <button type="button" :disabled="isSubmitting || isLoading || !canDownload"
                :title="canDownload ? '' : '请先填写所有必填内容（报告除外）'" @click="ppt.saveAndDownload()"
                :class="compact ? 'h-9' : 'h-11'"
                class="inline-flex items-center gap-2 px-5 rounded-xl text-sm font-semibold ease-out text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:shadow-none disabled:hover:translate-y-0 disabled:cursor-not-allowed focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600 outline-none">
                <svg v-if="!isSubmitting" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                </svg>
                <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
                {{ isSubmitting ? '处理中...' : '保存并下载' }}
            </button>
        </template>

        <button v-else type="button" :disabled="isSubmitting || isLoading" @click="ppt.download()"
            :class="compact ? 'h-9' : 'h-11'"
            class="inline-flex items-center gap-2 px-5 rounded-xl text-sm font-semibold ease-out text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
            </svg>
            {{ isSubmitting ? '处理中...' : '下载' }}
        </button>
    </div>
</template>
