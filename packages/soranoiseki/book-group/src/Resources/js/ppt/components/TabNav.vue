<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../store/powerpoint';
import type { TabId } from '../types';

defineProps<{ compact?: boolean }>();

const ppt = usePowerpointStore();
const { activeTab, tabProgress } = storeToRefs(ppt);

const tabs: Array<{ id: TabId; label: string; icon: string }> = [
    { id: 'pray', label: '代祷', icon: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z' },
    { id: 'scripture', label: '经文', icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25' },
    { id: 'preach', label: '讲道', icon: 'M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z' },
    { id: 'song', label: '诗歌', icon: 'M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z' },
    { id: 'report', label: '报告', icon: 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z' },
    { id: 'worker', label: '服事', icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' },
];

</script>

<template>
    <!-- Segmented tab bar with required-field progress; scrolls horizontally on narrow screens -->
    <div class="overflow-x-auto">
        <nav class="inline-flex min-w-full sm:min-w-0 gap-1 p-1 rounded-2xl bg-gray-100 dark:bg-gray-900" role="tablist"
            aria-label="PPT内容">
            <button v-for="t in tabs" :key="t.id" type="button" role="tab" :aria-selected="activeTab === t.id"
                @click="ppt.setActiveTab(t.id)" :class="[
                    'flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 rounded-xl text-sm font-semibold whitespace-nowrap transition-all duration-300 ease-out outline-none focus-visible:ring-2 focus-visible:ring-primary-500',
                    compact ? 'py-1.5' : 'py-2.5',
                    activeTab === t.id
                        ? 'bg-white text-primary-800 shadow-sm dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 hover:text-main hover:bg-white/60 dark:hover:bg-gray-800',
                ]">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path :d="t.icon" />
                </svg>
                {{ t.label }}

                <!-- Required tabs: check when complete, x/y otherwise. Optional tabs (total 0): check once filled. -->
                <svg v-if="tabProgress[t.id].total > 0 ? tabProgress[t.id].filled === tabProgress[t.id].total : tabProgress[t.id].filled > 0"
                    class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-label="已完成">
                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.2 14.4-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4-6.4 6.4Z" />
                </svg>
                <span v-else-if="tabProgress[t.id].total > 0"
                    class="px-1.5 py-0.5 rounded-full text-[11px] leading-none font-bold bg-orange-100 text-orange-700 tabular-nums"
                    :title="`必填项 ${tabProgress[t.id].filled}/${tabProgress[t.id].total}`">
                    {{ tabProgress[t.id].filled }}/{{ tabProgress[t.id].total }}
                </span>
            </button>
        </nav>
    </div>

</template>
