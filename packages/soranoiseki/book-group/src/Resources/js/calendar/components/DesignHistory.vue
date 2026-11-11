<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import type { HistoryEntry } from '../types';

// Every change of the design is recorded on the server (who, when, and for a restore, which record it went back to).
// Only the latest ones are listed here.
const cal = useCalendarStore();
const { history, historyTotal, designDirty, isImporting } = storeToRefs(cal);

function formatTime(iso: string): string {
    const d = new Date(iso);
    return Number.isNaN(d.getTime())
        ? iso
        : d.toLocaleString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function who(name: string | null): string {
    return name || '未知用户';
}

const actionLabel: Record<HistoryEntry['action'], string> = {
    create: '创建',
    save: '保存',
    restore: '恢复',
    initial: '修改前的状态',
};

/** What differs from the design that is current now */
function difference(entry: HistoryEntry): string {
    const parts = [];
    if (entry.settings_changed) parts.push(`${entry.settings_changed} 项参数不同`);
    if (entry.styles_changed) parts.push('自定义 CSS 不同');
    return parts.length ? '与当前相比：' + parts.join('，') : '与当前相同';
}

async function restore(entry: HistoryEntry): Promise<void> {
    const warning = designDirty.value ? '\n表单中尚未保存的修改会被丢弃。' : '';
    if (confirm(`确定要恢复到 ${formatTime(entry.created_at)}（${who(entry.user_name)}）的外观吗？\n这会作为一次新的修改被记录下来。${warning}`)) {
        await cal.restoreDesign(entry.id);
    }
}
</script>

<template>
    <section class="mt-8">
        <h3 class="mb-1 text-sm font-bold text-main dark:text-gray-100">修改记录</h3>
        <p class="mb-3 text-xs text-gray-500">
            显示最近 {{ history.length }} 条<template v-if="historyTotal > history.length">（共 {{ historyTotal }} 条）</template>。
        </p>

        <div v-if="!history.length" class="rounded-2xl bg-gray-50 dark:bg-gray-900 px-4 py-6 text-center text-sm text-gray-500">
            还没有修改记录。保存外观后会出现在这里。
        </div>

        <ul v-else class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
            <li v-for="(entry, index) in history" :key="entry.id" class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-main dark:text-gray-100">
                        <span class="font-medium tabular-nums">{{ formatTime(entry.created_at) }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                            :class="entry.action === 'restore' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : entry.action === 'initial' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : entry.action === 'create' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-200'">
                            {{ actionLabel[entry.action] }}
                        </span>
                        <span v-if="index === 0" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">当前</span>
                    </div>

                    <div class="mt-0.5 text-xs text-gray-500">
                        <span v-if="entry.action !== 'initial'" class="font-medium text-gray-600">修改人：{{ who(entry.user_name) }}</span>
                        <span v-else class="font-medium text-gray-600">首次记录前的原始状态</span>
                        <template v-if="entry.restored_from">
                            <span class="mx-1.5 text-gray-300">·</span>
                            恢复到 {{ formatTime(entry.restored_from.created_at) }}（{{ who(entry.restored_from.user_name) }}）的修改
                        </template>
                        <template v-if="index > 0">
                            <span class="mx-1.5 text-gray-300">·</span>{{ difference(entry) }}
                        </template>
                    </div>
                </div>

                <!-- Every record has the button; for the current design it is disabled because there is nothing to restore -->
                <button type="button" :disabled="isImporting || index === 0" :title="index === 0 ? '这已经是当前的外观' : '恢复到这条记录的外观'"
                    @click="restore(entry)"
                    class="shrink-0 h-8 px-3 rounded-lg text-xs font-semibold text-primary-800 bg-primary-100/70 hover:bg-primary-100 dark:text-primary-200 dark:bg-primary-900/50 dark:hover:bg-primary-900/80 disabled:opacity-40 disabled:hover:bg-primary-100/70 disabled:cursor-not-allowed transition">
                    恢复到此
                </button>
            </li>
        </ul>
    </section>
</template>
