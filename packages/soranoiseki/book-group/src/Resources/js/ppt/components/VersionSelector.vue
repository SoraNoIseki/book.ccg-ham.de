<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../store/powerpoint';
import UiSingleSelect from '@book/task-plan/components/ui/UiSingleSelect.vue';

defineProps<{ compact?: boolean }>();

const ppt = usePowerpointStore();
const { date, selectedDate, versionOptions, isLoading, saveEnabled } = storeToRefs(ppt);
</script>

<template>
    <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
        <!-- Current version: date + editable/readonly badge -->
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center rounded-xl bg-primary-100 text-primary-800 transition-all duration-300 ease-out"
                :class="compact ? 'w-8 h-8' : 'w-11 h-11'">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <path d="M16 2v4M8 2v4M3 10h18" />
                </svg>
            </div>
            <div>
                <div class="text-xs text-gray-500 overflow-hidden transition-all duration-300 ease-out"
                    :class="compact ? 'max-h-0 opacity-0' : 'max-h-5 opacity-100'">当前版本</div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-main dark:text-gray-100 tabular-nums transition-all duration-300 ease-out"
                        :class="compact ? 'text-base' : 'text-lg'">{{ date || '--' }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                        :class="saveEnabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                        {{ saveEnabled ? '本周 · 可编辑' : '历史 · 仅下载' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Version search -->
        <div class="w-full sm:w-64 sm:ml-auto lg:ml-0">
            <div class="text-xs text-gray-500 overflow-hidden transition-all duration-300 ease-out"
                :class="compact ? 'max-h-0 opacity-0 mb-0' : 'max-h-5 opacity-100 mb-1'">切换版本</div>
            <UiSingleSelect v-model="selectedDate" :options="versionOptions" :disabled="isLoading"
                placeholder="选择或搜索日期..." @change="(v: string) => v && ppt.loadVersion(v)" />
        </div>
    </div>
</template>
