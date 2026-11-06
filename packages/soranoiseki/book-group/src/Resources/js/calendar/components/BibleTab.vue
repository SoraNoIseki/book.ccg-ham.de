<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import CsvImport from './CsvImport.vue';

const cal = useCalendarStore();
const { details, year, editable } = storeToRefs(cal);

function checkRow(row: Record<string, string>): string | null {
    const month = Number(row.month);
    if (!Number.isInteger(month) || month < 1 || month > 12) return '月份应为 1 至 12';
    if (row.year && Number(row.year) !== year.value) return `年份不是 ${year.value}`;
    if (!row.text) return '经文为空';
    return null;
}

/** Always show all 12 months so that missing ones are obvious */
const months = computed(() =>
    Array.from({ length: 12 }, (_, i) => {
        const d = details.value.find((x) => x.month === i + 1);
        return { month: i + 1, text: d?.bible_text ?? '', source: d?.bible_source ?? '' };
    }),
);
</script>

<template>
    <div class="space-y-8">
        <p v-if="!editable" class="text-sm text-gray-600 dark:text-gray-300">历史版本只能查看，不能上传或修改。</p>

        <CsvImport v-if="editable" kind="bible-texts" title="每月经文" :columns="['year', 'month', 'text', 'source']"
            :labels="['年份', '月份', '经文', '出处']" template-name="每月经文模板.csv"
            :template-rows="[[String(year), '1', '你们要常常喜乐|不住地祷告|凡事谢恩', '帖撒罗尼迦前书 5章16-18节']]"
            :check-row="checkRow" warning="同一年同一月已有的经文会被更新，其他月份保持不变。"
            description="上传每月经文 CSV（UTF-8，含表头 year、month、text、source）。经文里换行用竖线 | 分隔，显示在每月图片的下方。" />

        <section>
            <h3 class="mb-3 text-sm font-bold text-main dark:text-gray-100">
                已导入的每月经文
                <span class="ml-1 font-normal text-gray-500">{{ cal.monthDetailsDone }}/12 个月</span>
            </h3>
            <div class="overflow-auto rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2 w-20">月份</th>
                            <th class="px-4 py-2">经文</th>
                            <th class="px-4 py-2 whitespace-nowrap">出处</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in months" :key="m.month" class="border-t border-gray-100 dark:border-gray-700">
                            <td class="px-4 py-2 align-top font-semibold whitespace-nowrap">{{ m.month }} 月</td>
                            <td v-if="m.text" class="px-4 py-2 whitespace-pre-line">{{ m.text.replace(/\|/g, '\n') }}</td>
                            <td v-else class="px-4 py-2 text-orange-600">未设置</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap text-gray-600">{{ m.source }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
