<script setup lang="ts">
import { onMounted, ref } from 'vue';

// Dismissible update notice. Dismissal is remembered in the browser (localStorage) per notice id.
// Change `ANNOUNCEMENT_ID` for every new notice so it shows again.
const ANNOUNCEMENT_ID = '2026-11-basic-tab';
const STORAGE_KEY = `ppt-announcement-dismissed:${ANNOUNCEMENT_ID}`;

const visible = ref(false);

onMounted(() => {
    try {
        visible.value = localStorage.getItem(STORAGE_KEY) !== '1';
    } catch {
        visible.value = true; // storage blocked: just show it
    }
});

function dismiss(): void {
    visible.value = false;
    try {
        localStorage.setItem(STORAGE_KEY, '1');
    } catch {
        /* ignore */
    }
}
</script>

<template>
    <div v-if="visible" role="status"
        class="flex items-start gap-3 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-200/80 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-800">
        <span class="inline-flex h-[22.75px] shrink-0 items-center rounded-md bg-sky-100 px-1.5 text-xs font-semibold text-sky-700 dark:bg-sky-900 dark:text-sky-300">更新</span>
        <ul class="min-w-0 flex-1 list-disc space-y-0.5 pl-4 leading-relaxed marker:text-sky-400">
            <li>页面改版</li>
            <li>新增“基础”标签页：可选择圣餐流程版本，开关钟声视频</li>
            <li>经文已支持多段经文输入（每行一段）</li>
            <li>启应经文可以从库中选择，也支持按章节输入多段经文</li>
            <li>“格式说明”改为抽屉形式</li>
        </ul>
        <button type="button" aria-label="关闭公告" @click="dismiss"
            class="-mt-0.5 shrink-0 rounded-md p-1 text-sky-600 hover:bg-sky-100 dark:text-sky-300 dark:hover:bg-sky-900 outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>
</template>
