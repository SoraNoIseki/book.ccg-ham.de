<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import CsvImport from './CsvImport.vue';

const cal = useCalendarStore();
const { events, year } = storeToRefs(cal);

function checkRow(row: Record<string, string>): string | null {
    if (!row.event) return '事件内容为空';
    const m = row.date.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/);
    if (!m) return '日期格式应为 YYYY-MM-DD';
    const [, y, mo, d] = m.map(Number);
    const dt = new Date(y, mo - 1, d);
    if (dt.getMonth() !== mo - 1 || dt.getDate() !== d) return '日期不存在';
    if (y !== year.value && !(y === year.value - 1 && mo === 12) && !(y === year.value + 1 && mo === 1)) {
        return `日期不在 ${year.value} 年内`;
    }
    return null;
}

const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
function weekday(date: string): string {
    const [y, m, d] = date.split('-').map(Number);
    return '周' + weekdays[new Date(y, m - 1, d).getDay()];
}
</script>

<template>
    <div class="space-y-8">
        <CsvImport kind="events" title="大事历" :columns="['date', 'event']" :labels="['日期', '事件']"
            template-name="大事历模板.csv"
            :template-rows="[[`${year}-01-18`, '会友大会|登录事选举'], [`${year}-04-05`, '复活节主日']]"
            :check-row="checkRow"
            :warning="events.length ? `导入将替换 ${year} 年现有的 ${events.length} 条大事历。` : undefined"
            description="上传大事历 CSV（UTF-8，含表头 date 和 event）。同一天有多行内容时，用竖线 | 分隔，日历里会显示为换行。" />

        <section>
            <h3 class="mb-3 text-sm font-bold text-main dark:text-gray-100">
                已导入的大事历
                <span class="ml-1 font-normal text-gray-500">共 {{ events.length }} 条</span>
            </h3>
            <div v-if="!events.length" class="rounded-2xl bg-gray-50 dark:bg-gray-900 px-4 py-8 text-center text-sm text-gray-500">
                还没有大事历，请先上传 CSV。
            </div>
            <div v-else class="max-h-[28rem] overflow-auto rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="sticky top-0 text-xs text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2 whitespace-nowrap">日期</th>
                            <th class="px-4 py-2">事件</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in events" :key="e.id" class="border-t border-gray-100 dark:border-gray-700">
                            <td class="px-4 py-2 align-top whitespace-nowrap tabular-nums">
                                {{ e.date }} <span class="text-gray-400">{{ weekday(e.date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-pre-line">{{ e.name.replace(/\|/g, '\n') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
