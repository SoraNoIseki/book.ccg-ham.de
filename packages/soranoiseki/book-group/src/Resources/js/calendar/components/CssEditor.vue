<script setup lang="ts">
import { computed, ref } from 'vue';

// Free-form CSS appended after the base stylesheet. Server-side checks reject unsafe content;
// `warnings` are the problems the PDF engine reported for the last preview (unknown property, ...).
const MAX_BYTES = 20480;

const model = defineModel<string>({ required: true });
defineProps<{ warnings: string[] }>();

const open = ref(model.value.trim() !== '');

const bytes = computed(() => new TextEncoder().encode(model.value).length);
const tooLong = computed(() => bytes.value > MAX_BYTES);

// Click to insert a rule skeleton for a part of the page
const selectors: Array<{ label: string; selector: string }> = [
    { label: '整页', selector: '.page' },
    { label: '某个月', selector: '.month-4' },
    { label: '每月图片', selector: '.header' },
    { label: '经文', selector: '.verse' },
    { label: '月份标题', selector: '.title-inner' },
    { label: '星期栏', selector: '.weekday' },
    { label: '周末星期', selector: '.weekday.weekend' },
    { label: '日期格子', selector: '.day-cell' },
    { label: '非本月日期', selector: '.day-cell.other-month' },
    { label: '某一天', selector: '.day-cell[data-date="2027-01-01"]' },
    { label: '日期数字', selector: '.day' },
    { label: '农历', selector: '.lunar' },
    { label: '节假日名称', selector: '.holiday' },
    { label: '大事历', selector: '.event' },
    { label: '页脚', selector: '.footer' },
];

function insert(selector: string): void {
    const sep = model.value.trim() === '' ? '' : '\n\n';
    model.value = model.value.replace(/\s+$/, '') + `${sep}${selector} {\n  \n}\n`;
}
</script>

<template>
    <section class="mt-8 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <button type="button" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left"
            :aria-expanded="open" @click="open = !open">
            <span class="text-sm font-bold text-main dark:text-gray-100">
                高级：自定义 CSS
                <span v-if="model.trim()" class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-200">已启用</span>
            </span>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </button>

        <div v-show="open" class="px-4 pb-4 space-y-3">
            <p class="text-xs leading-relaxed text-gray-500">
                这里的样式排在最后，会覆盖上面的参数和基础样式。<b>格子内部的元素</b>（日期、农历、大事历、节假日）可以放心调整；
                每月图片、标题、星期栏和日期网格的<b>整体位置</b>由程序按纸张尺寸计算，请用上面的参数调整，否则容易错位。
                可以使用参数对应的变量，例如 <code class="px-1 rounded bg-gray-100 dark:bg-gray-700">var(--accent)</code>、
                <code class="px-1 rounded bg-gray-100 dark:bg-gray-700">var(--weekend)</code>。图片请直接写文件名，例如
                <code class="px-1 rounded bg-gray-100 dark:bg-gray-700">url(01.jpg)</code>（取自本年度的图片目录）。
            </p>

            <textarea v-model="model" rows="10" spellcheck="false" autocapitalize="off" autocomplete="off"
                placeholder=".day-cell[data-date=&quot;2027-01-01&quot;] {&#10;  background: #ffe066;&#10;}"
                class="w-full rounded-xl border bg-gray-50/60 dark:bg-gray-900 p-3 text-[13px] leading-relaxed font-mono outline-none transition focus:bg-white focus:ring-2"
                :class="tooLong ? 'border-red-300 focus:ring-red-200' : 'border-gray-200 focus:ring-primary-500/40 focus:border-primary-500'"></textarea>

            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <span :class="tooLong ? 'text-red-600 font-medium' : 'text-gray-400'">
                    {{ (bytes / 1024).toFixed(1) }} / {{ MAX_BYTES / 1024 }} KB
                </span>
                <button v-if="model.trim()" type="button" class="font-medium text-gray-500 hover:text-red-600 transition"
                    @click="model = ''">清除自定义 CSS</button>
            </div>

            <!-- Problems reported by the PDF engine -->
            <div v-if="warnings.length" role="status" class="p-3 text-xs rounded-xl bg-amber-50 ring-1 ring-amber-200 text-amber-900">
                <div class="font-semibold mb-1">预览发现 {{ warnings.length }} 个问题（这些写法已被忽略）：</div>
                <ul class="space-y-0.5 list-disc pl-4">
                    <li v-for="(w, i) in warnings" :key="i" class="break-all">{{ w }}</li>
                </ul>
            </div>

            <!-- Selector reference -->
            <div>
                <div class="mb-1.5 text-xs font-semibold text-gray-600">可用的选择器（点击插入规则）</div>
                <div class="flex flex-wrap gap-1.5">
                    <button v-for="s in selectors" :key="s.selector" type="button" :title="s.selector" @click="insert(s.selector)"
                        class="px-2.5 py-1 rounded-lg text-xs bg-gray-100 text-gray-700 hover:bg-primary-100 hover:text-primary-800 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:hover:text-white transition">
                        {{ s.label }}
                    </button>
                </div>
                <p class="mt-2 text-[11px] text-gray-400">
                    例：<code>.month-4 .weekday { letter-spacing: 0.3em; }</code> 只改 4 月的星期栏；
                    <code>.day-cell[data-date="2027-04-05"] { background: #ffe066; }</code> 只改某一天。
                </p>
            </div>
        </div>
    </section>
</template>
