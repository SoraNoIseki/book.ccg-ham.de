<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import CsvImport from './CsvImport.vue';
import EditButton from './EditButton.vue';
import ExportButton from './ExportButton.vue';
import { buildCsv, downloadText } from '../csv';
import BibleEditSheet from './BibleEditSheet.vue';

const cal = useCalendarStore();
const { details, year, editable, isImporting } = storeToRefs(cal);

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

const editing = ref<number | null>(null);

/** Same columns as the upload (year, month, text, source); months without a verse are left out */
function exportCsv(): void {
    const rows = months.value.filter((m) => m.text).map((m) => [String(year.value), String(m.month), m.text, m.source]);
    downloadText(`每月经文_${year.value}.csv`, buildCsv(['year', 'month', 'text', 'source'], rows));
}
const editRow = computed(() => months.value.find((m) => m.month === editing.value));

// --- Rearranging -----------------------------------------------------------------------------
// The month labels stay where they are, the verses are swapped. Changes are made on a draft and saved at once.
interface DraftRow { from: number; text: string; source: string }

const draft = ref<DraftRow[] | null>(null);
const ordering = computed(() => draft.value !== null);
const rows = computed<DraftRow[]>(() => draft.value ?? months.value.map((m) => ({ from: m.month, text: m.text, source: m.source })));
const changed = computed(() => !!draft.value?.some((r, i) => r.from !== i + 1));
/** Index of the row chosen for a swap, waiting for the second one */
const swapFrom = ref<number | null>(null);

function startOrdering(): void {
    cal.clearMessages();
    swapFrom.value = null;
    draft.value = months.value.map((m) => ({ from: m.month, text: m.text, source: m.source }));
}

function stopOrdering(): void {
    draft.value = null;
    swapFrom.value = null;
}

function swap(i: number): void {
    if (!draft.value) return;
    if (swapFrom.value === null) {
        swapFrom.value = i;
        return;
    }
    const j = swapFrom.value;
    swapFrom.value = null;
    if (j === i) return;
    const next = [...draft.value];
    [next[i], next[j]] = [next[j], next[i]];
    draft.value = next;
}

async function saveOrder(): Promise<void> {
    if (!draft.value) return;
    if (await cal.reorderBibleTexts(draft.value.map((r) => r.from))) stopOrdering();
}

// A draft belongs to one calendar: drop it when another year is shown
watch(year, stopOrdering);

const iconBtn = 'inline-flex items-center justify-center w-9 h-9 rounded-lg text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40 hover:bg-primary-100/60 dark:hover:bg-gray-700 active:scale-95 transition disabled:opacity-30 disabled:cursor-not-allowed disabled:active:scale-100 outline-none focus-visible:ring-2 focus-visible:ring-primary-600';
</script>

<template>
    <div class="space-y-8">
        <p v-if="!editable" class="text-sm text-gray-600 dark:text-gray-300">历史版本只能查看，不能上传或修改。</p>

        <CsvImport v-if="editable && !ordering" kind="bible-texts" title="每月经文" :columns="['year', 'month', 'text', 'source']"
            :labels="['年份', '月份', '经文', '出处']" template-name="每月经文模板.csv"
            :template-rows="[[String(year), '1', '你们要常常喜乐|不住地祷告|凡事谢恩', '帖撒罗尼迦前书 5章16-18节']]"
            :check-row="checkRow" warning="同一年同一月已有的经文会被更新，其他月份保持不变。"
            description="上传每月经文 CSV（UTF-8，含表头 year、month、text、source）。经文里换行用竖线 | 分隔，显示在每月图片的下方。" />

        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-main dark:text-gray-100">
                    已导入的每月经文
                    <span class="ml-1 font-normal text-gray-500">{{ cal.monthDetailsDone }}/12 个月</span>
                </h3>
                <div v-if="ordering" class="flex gap-2">
                    <button type="button" @click="stopOrdering"
                        class="h-9 px-4 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 active:scale-95 transition">取消</button>
                    <button type="button" :disabled="!changed || isImporting" @click="saveOrder"
                        class="h-9 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                        {{ isImporting ? '保存中...' : '保存顺序' }}
                    </button>
                </div>
                <div v-else class="flex flex-wrap gap-2">
                    <button v-if="editable" type="button" :disabled="!cal.monthDetailsDone" @click="startOrdering"
                        class="inline-flex items-center gap-2 whitespace-nowrap h-9 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 transition disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 4v16M7 4 3.5 7.5M7 4l3.5 3.5M17 20V4M17 20l-3.5-3.5M17 20l3.5-3.5" />
                        </svg>
                        调整顺序
                    </button>
                    <ExportButton :disabled="!cal.monthDetailsDone" @click="exportCsv">导出 CSV</ExportButton>
                </div>
            </div>
            <p v-if="ordering" class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                <template v-if="swapFrom !== null">请选择要与 <b>{{ swapFrom + 1 }} 月</b> 对调的月份（再点一次取消）。</template>
                <template v-else>点一个月份的「对调」，再点另一个月份的「对调」，两个月的经文就会互换。改完点「保存顺序」。</template>
            </p>
            <div class="overflow-auto rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2 w-20">月份</th>
                            <th class="px-4 py-2">经文</th>
                            <th class="px-4 py-2 whitespace-nowrap">出处</th>
                            <th v-if="editable" class="px-4 py-2"><span class="sr-only">操作</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(m, i) in rows" :key="m.from" class="border-t border-gray-100 dark:border-gray-700 transition-colors"
                            :class="[
                                ordering && m.from !== i + 1 ? 'bg-amber-50/70 dark:bg-amber-900/20' : '',
                                ordering && swapFrom === i ? '!bg-primary-100/70 dark:!bg-primary-900/40' : '',
                            ]"
>
                            <td class="px-4 py-2 align-middle font-semibold whitespace-nowrap">
                                {{ i + 1 }} 月
                                <div v-if="ordering && m.from !== i + 1" class="text-xs font-normal text-amber-700 dark:text-amber-400">原 {{ m.from }} 月</div>
                            </td>
                            <td v-if="m.text" class="px-4 py-2 align-middle whitespace-pre-line">{{ m.text.replace(/\|/g, '\n') }}</td>
                            <td v-else class="px-4 py-2 align-middle text-orange-600">未设置</td>
                            <td class="px-4 py-2 align-middle whitespace-nowrap text-gray-600 dark:text-gray-400">{{ m.source }}</td>
                            <td v-if="editable" class="px-4 py-2 align-middle text-right whitespace-nowrap">
                                <div v-if="ordering" class="inline-flex items-center gap-1.5">
                                    <button type="button" :aria-pressed="swapFrom === i" :aria-label="`对调 ${i + 1} 月`" @click="swap(i)"
                                        :class="[iconBtn, 'w-auto px-3 text-sm font-semibold whitespace-nowrap', swapFrom === i ? '!bg-primary-600 !text-white' : '']">对调</button>
                                </div>
                                <EditButton v-else :label="`编辑 ${m.from} 月经文`" @click="editing = m.from" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <BibleEditSheet :month="editing" :text="editRow?.text ?? ''" :source="editRow?.source ?? ''" @close="editing = null" />
    </div>
</template>
