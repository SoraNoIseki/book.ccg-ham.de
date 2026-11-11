<script setup lang="ts">
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import { buildCsv, downloadText, parseCsv, type ParsedCsv } from '../csv';

// Upload flow for a CSV: choose file -> preview and check it in the browser -> confirm import.
const props = defineProps<{
    kind: 'events' | 'bible-texts';
    title: string;
    description: string;
    /** Required header names (lower case), in the order of the template */
    columns: string[];
    /** Chinese label per column, shown in the preview table */
    labels: string[];
    templateName: string;
    templateRows: string[][];
    /** Returns a problem text for a row, or null when the row is fine */
    checkRow: (row: Record<string, string>) => string | null;
    /** Warning shown above the confirm button, e.g. that existing data will be replaced */
    warning?: string;
}>();

const cal = useCalendarStore();
const { isImporting } = storeToRefs(cal);

const input = ref<HTMLInputElement | null>(null);
const file = ref<File | null>(null);
const parsed = ref<ParsedCsv | null>(null);
const readError = ref<string | null>(null);
const dragging = ref(false);

const PREVIEW_LIMIT = 30;

const missingColumns = computed(() => (parsed.value ? props.columns.filter((c) => !parsed.value!.headers.includes(c)) : []));

/** Rows as objects keyed by header name, with the problem (if any) of each row */
const checked = computed(() => {
    if (!parsed.value || missingColumns.value.length) return [];
    const idx = props.columns.map((c) => parsed.value!.headers.indexOf(c));
    return parsed.value.rows.map((cells) => {
        const row: Record<string, string> = {};
        props.columns.forEach((c, i) => (row[c] = (cells[idx[i]] ?? '').trim()));
        return { row, problem: props.checkRow(row) };
    });
});

const problemCount = computed(() => checked.value.filter((r) => r.problem).length);
const canImport = computed(() => !!file.value && checked.value.length > 0 && !missingColumns.value.length && !isImporting.value);

async function choose(f: File | undefined | null): Promise<void> {
    reset();
    if (!f) return;
    if (!/\.(csv|txt)$/i.test(f.name)) {
        readError.value = '请选择 CSV 文件（.csv）。';
        return;
    }
    try {
        parsed.value = parseCsv(await f.text());
        file.value = f;
    } catch {
        readError.value = '无法读取该文件。';
    }
}

function reset(): void {
    file.value = null;
    parsed.value = null;
    readError.value = null;
}

function onPick(e: Event): void {
    const el = e.target as HTMLInputElement;
    choose(el.files?.[0]);
    el.value = '';
}

function onDrop(e: DragEvent): void {
    dragging.value = false;
    choose(e.dataTransfer?.files?.[0]);
}

async function confirmImport(): Promise<void> {
    if (!file.value) return;
    if (await cal.importCsv(props.kind, file.value)) reset();
}

function downloadTemplate(): void {
    downloadText(props.templateName, buildCsv(props.columns, props.templateRows));
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-300 max-w-2xl">{{ description }}</p>
            <button type="button" @click="downloadTemplate"
                class="inline-flex items-center gap-2 h-9 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                </svg>
                下载模板
            </button>
        </div>

        <!-- Drop zone -->
        <div v-if="!file" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop"
            class="rounded-2xl border-2 border-dashed transition"
            :class="dragging ? 'border-primary-500 bg-primary-100/40 dark:bg-primary-900/30' : 'border-gray-300 bg-gray-50 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-600'">
            <button type="button" class="w-full py-8 flex flex-col items-center gap-2 text-gray-500" @click="input?.click()">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" />
                </svg>
                <span class="text-sm"><b class="text-primary-700 dark:text-primary-300">点击选择</b> 或拖入 CSV 文件</span>
                <span class="text-xs text-gray-400">必需的列：{{ columns.join('、') }}</span>
            </button>
            <input ref="input" type="file" accept=".csv,.txt,text/csv" class="hidden" @change="onPick" />
        </div>
        <p v-if="readError" class="text-sm text-red-700">{{ readError }}</p>

        <!-- Preview -->
        <div v-if="file && parsed" class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-900">
                <div class="text-sm">
                    <span class="font-semibold text-main dark:text-gray-100">{{ file.name }}</span>
                    <span class="ml-2 text-gray-500">共 {{ checked.length }} 行</span>
                    <span v-if="problemCount" class="ml-2 text-orange-600 font-medium">{{ problemCount }} 行需要检查</span>
                    <span v-else-if="checked.length" class="ml-2 text-green-700 font-medium">格式检查通过</span>
                </div>
                <button type="button" class="text-sm text-gray-500 hover:text-red-600" @click="reset">重新选择</button>
            </div>

            <div v-if="missingColumns.length" class="px-4 py-3 text-sm text-red-800 bg-red-50">
                缺少必需的列：<b>{{ missingColumns.join('、') }}</b>。文件中的列为：{{ parsed.headers.join('、') || '（空）' }}。
            </div>
            <div v-else-if="!checked.length" class="px-4 py-3 text-sm text-red-800 bg-red-50">文件中没有数据行。</div>

            <div v-else class="max-h-96 overflow-auto">
                <table class="w-full text-sm text-left">
                    <thead class="sticky top-0 text-xs text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2 w-12">#</th>
                            <th v-for="(l, i) in labels" :key="columns[i]" class="px-4 py-2 whitespace-nowrap">{{ l }}</th>
                            <th class="px-4 py-2 whitespace-nowrap">检查</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in checked.slice(0, PREVIEW_LIMIT)" :key="i"
                            class="border-t border-gray-100 dark:border-gray-700" :class="r.problem ? 'bg-orange-50/60' : ''">
                            <td class="px-4 py-2 text-gray-400 tabular-nums">{{ i + 1 }}</td>
                            <td v-for="c in columns" :key="c" class="px-4 py-2 align-top whitespace-pre-line">{{ (r.row[c] ?? '').replace(/\|/g, '\n') }}</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap" :class="r.problem ? 'text-orange-700' : 'text-green-700'">
                                {{ r.problem ?? '✓' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="checked.length > PREVIEW_LIMIT" class="px-4 py-2 text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 dark:text-gray-400">
                    仅预览前 {{ PREVIEW_LIMIT }} 行，其余 {{ checked.length - PREVIEW_LIMIT }} 行将一并导入。
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                <p v-if="warning" class="text-xs text-orange-700 max-w-xl">{{ warning }}</p>
                <span v-else />
                <button type="button" :disabled="!canImport" @click="confirmImport"
                    class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg transition disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed">
                    <svg v-if="isImporting" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" />
                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                    {{ isImporting ? '导入中...' : '确认导入' }}
                </button>
            </div>
        </div>
    </div>
</template>
