<script setup lang="ts">
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import CsvImport from './CsvImport.vue';
import EditButton from './EditButton.vue';
import ExportButton from './ExportButton.vue';
import { buildCsv, downloadText } from '../csv';
import { inEventRange } from '../eventRange';
import EventEditSheet from './EventEditSheet.vue';
import type { CalendarEvent } from '../types';

const cal = useCalendarStore();
const { events, year, editable } = storeToRefs(cal);

function checkRow(row: Record<string, string>): string | null {
    if (!row.event) return '事件内容为空';
    const m = row.date.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/);
    if (!m) return '日期格式应为 YYYY-MM-DD';
    const [, y, mo, d] = m.map(Number);
    const dt = new Date(y, mo - 1, d);
    if (dt.getMonth() !== mo - 1 || dt.getDate() !== d) return '日期不存在';
    if (!inEventRange(year.value, `${y}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`)) {
        return `日期不在 ${year.value} 年内`;
    }
    return null;
}

const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
function weekday(date: string): string {
    const [y, m, d] = date.split('-').map(Number);
    return '周' + weekdays[new Date(y, m - 1, d).getDay()];
}

const editing = ref<CalendarEvent | null>(null);

/** Same columns as the upload (date, event), so the file can be edited and uploaded again */
function exportCsv(): void {
    downloadText(`大事历_${year.value}.csv`, buildCsv(['date', 'event'], events.value.map((e) => [e.date, e.name])));
}
</script>

<template>
    <div class="space-y-8">
        <p v-if="!editable" class="text-sm text-gray-600 dark:text-gray-300">历史版本只能查看，不能上传或修改。</p>

        <CsvImport v-if="editable" kind="events" title="大事历" :columns="['date', 'event']" :labels="['日期', '事件']"
            template-name="大事历模板.csv"
            :template-rows="[[`${year}-01-18`, '大事历1|大事历2'], [`${year}-04-05`, '大事历3']]"
            :check-row="checkRow"
            :warning="events.length ? `导入将替换 ${year} 年现有的 ${events.length} 条大事历。` : undefined"
            description="上传大事历 CSV（UTF-8，含表头 date 和 event）。同一天有多行内容时，用竖线 | 分隔，日历里会显示为换行。" />

        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-main dark:text-gray-100">
                    已导入的大事历
                    <span class="ml-1 font-normal text-gray-500">共 {{ events.length }} 条</span>
                </h3>
                <ExportButton :disabled="!events.length" @click="exportCsv">导出 CSV</ExportButton>
            </div>
            <div v-if="!events.length" class="rounded-2xl bg-gray-50 dark:bg-gray-900 px-4 py-8 text-center text-sm text-gray-500">
                还没有大事历，请先上传 CSV。
            </div>
            <div v-else class="overflow-x-auto rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2 whitespace-nowrap">日期</th>
                            <th class="px-4 py-2">事件</th>
                            <th v-if="editable" class="px-4 py-2"><span class="sr-only">操作</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in events" :key="e.id" class="border-t border-gray-100 dark:border-gray-700">
                            <td class="px-4 py-2 align-middle whitespace-nowrap tabular-nums">
                                {{ e.date }} <span class="text-gray-400">{{ weekday(e.date) }}</span>
                            </td>
                            <td class="px-4 py-2 align-middle whitespace-pre-line">{{ e.name.replace(/\|/g, '\n') }}</td>
                            <td v-if="editable" class="px-4 py-2 align-middle text-right whitespace-nowrap">
                                <EditButton :label="`编辑 ${e.date} 的大事历`" @click="editing = e" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <EventEditSheet :event="editing" @close="editing = null" />
    </div>
</template>
