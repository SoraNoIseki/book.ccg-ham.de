<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import { extractErrors } from '../../shared/http';
import SettingControl from './SettingControl.vue';
import CssEditor from './CssEditor.vue';
import DesignHistory from './DesignHistory.vue';
import DesignPicker from './DesignPicker.vue';
import DesignLibrary from './DesignLibrary.vue';
import type { SettingValue } from '../types';

const cal = useCalendarStore();
const { draft, draftStyles, settingsSchema, designDirty, calendarId, designId, canDesign, isImporting } = storeToRefs(cal);

const groups = computed(() =>
    settingsSchema.value.groups.map((g) => ({
        ...g,
        items: settingsSchema.value.settings.filter((s) => s.group === g.id),
    })),
);

function update(key: string, value: SettingValue): void {
    cal.draft = { ...cal.draft, [key]: value };
}

function revert(): void {
    cal.draft = { ...cal.settings };
    cal.draftStyles = cal.styles;
}

// ---------------- preview (one month, unsaved values; rendered on demand) ----------------
const month = ref(1);
const previewUrl = ref<string | null>(null);
const previewing = ref(false);
const previewError = ref<string | null>(null);
const cssWarnings = ref<string[]>([]);

// Snapshot of what the shown preview was rendered from, to tell when it is out of date
const currentKey = computed(() => JSON.stringify([month.value, designId.value, canDesign.value ? [draft.value, draftStyles.value] : null]));
const renderedKey = ref<string | null>(null);
const stale = computed(() => !!previewUrl.value && renderedKey.value !== currentKey.value);

let controller: AbortController | null = null;
let seq = 0;

async function renderPreview(): Promise<void> {
    if (!calendarId.value) return;
    const mine = ++seq;
    const keyAtStart = currentKey.value;
    controller?.abort(); // drop the request that is no longer current
    controller = new AbortController();
    previewing.value = true;
    previewError.value = null;

    try {
        const res = await axios.post<Blob>(
            `/api/calendar/${calendarId.value}/preview`,
            { month: month.value, design_id: designId.value, ...(canDesign.value ? { settings: draft.value, styles: draftStyles.value } : {}) },
            { responseType: 'blob', signal: controller.signal },
        );
        if (mine !== seq) return;
        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
        cssWarnings.value = parseWarnings(res.headers['x-calendar-warnings']);
        renderedKey.value = keyAtStart;
    } catch (e) {
        if (axios.isCancel(e) || mine !== seq) return;
        previewError.value = (await extractErrors(e, '预览生成失败。'))[0];
    } finally {
        if (mine === seq) previewing.value = false;
    }
}

/** The server lists CSS problems (unknown property, invalid value, ...) in a URL-encoded JSON header */
function parseWarnings(header: unknown): string[] {
    if (typeof header !== 'string' || !header) return [];
    try {
        const list = JSON.parse(decodeURIComponent(header));
        return Array.isArray(list) ? list.map(String) : [];
    } catch {
        return [];
    }
}

// A different calendar needs a new preview
watch(calendarId, () => {
    controller?.abort();
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    renderedKey.value = null;
    previewError.value = null;
    cssWarnings.value = [];
});

onBeforeUnmount(() => {
    controller?.abort();
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

const viewerSrc = computed(() => (previewUrl.value ? previewUrl.value + '#toolbar=0&navpanes=0&view=Fit' : null));
</script>

<template>
<div>
    <DesignPicker />
    <DesignLibrary />

    <!-- Notice: kept prominent on purpose, unsaved changes are easy to forget -->
    <div v-if="canDesign" role="note" class="mb-6 flex items-center gap-3 p-4 rounded-2xl bg-amber-50 ring-1 ring-amber-300 text-amber-900 shadow-sm">
        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-100 text-amber-600 shrink-0">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2 1 21h22L12 2Zm1 15h-2v-2h2v2Zm0-4h-2V9h2v4Z" />
            </svg>
        </span>
        <p class="text-sm sm:text-base font-semibold leading-snug">
            预览使用尚未保存的修改，修改结束后请记得<span class="px-1.5 py-0.5 mx-0.5 rounded-md bg-amber-200/70">保存</span>。
        </p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
        <!-- Settings form (only for users who may edit designs) -->
        <div v-if="canDesign">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    调整「{{ cal.designName }}」的颜色、字号和版面，必要时在下方补充自定义 CSS，然后点击右侧的「刷新预览」查看效果。
                    修改会影响所有使用这一套外观的日历。
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 py-3 mb-2">
                <button type="button" :disabled="!designDirty || isImporting" @click="cal.saveDesign()"
                    class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg transition disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed">
                    {{ isImporting ? '保存中...' : '保存外观' }}
                </button>
                <button type="button" :disabled="!designDirty" @click="revert"
                    class="h-10 px-4 rounded-xl text-sm font-semibold text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    放弃修改
                </button>
                <button type="button" @click="cal.resetDraft()"
                    class="h-10 px-4 rounded-xl text-sm font-semibold text-gray-600 hover:text-primary-700 transition">
                    参数全部恢复默认
                </button>
                <span v-if="designDirty" class="text-xs font-medium text-orange-600">有未保存的修改</span>
            </div>

            <section v-for="g in groups" :key="g.id" class="mt-6">
                <h3 class="mb-1 text-sm font-bold text-main dark:text-gray-100">{{ g.label }}</h3>
                <div>
                    <SettingControl v-for="item in g.items" :key="item.key" :item="item" :model-value="draft[item.key]"
                        @update:model-value="update(item.key, $event)" @reset="update(item.key, item.default)" />
                </div>
            </section>

            <CssEditor v-model="draftStyles" :warnings="cssWarnings" />

            <DesignHistory />
        </div>

        <!-- Without the design role: choosing and previewing only -->
        <div v-else class="rounded-2xl bg-gray-50 dark:bg-gray-900 p-6 text-sm text-gray-600 dark:text-gray-300 self-start">
            <p class="font-semibold text-main dark:text-gray-100 mb-1">只能选用和预览外观</p>
            <p>你没有「年历外观设计」权限，不能新建、修改或删除外观。可以在上面选用一套外观，点击右侧的「生成预览」查看效果。</p>
        </div>

        <!-- Preview -->
        <aside class="lg:sticky lg:top-44 self-start">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <label for="preview-month" class="text-sm font-semibold text-main dark:text-gray-100">预览月份</label>
                    <select id="preview-month" v-model.number="month"
                        class="h-9 pl-3 pr-8 rounded-lg border border-gray-200 bg-white text-sm outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500">
                        <option v-for="m in 12" :key="m" :value="m">{{ m }} 月</option>
                    </select>
                </div>
                <button type="button" :disabled="previewing" @click="renderPreview"
                    class="inline-flex items-center gap-1.5 h-9 px-4 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-sm hover:shadow transition disabled:opacity-60 disabled:cursor-wait outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
                    <svg class="w-4 h-4" :class="previewing ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5" />
                    </svg>
                    {{ previewUrl ? '刷新预览' : '生成预览' }}
                </button>
            </div>

            <div class="flex items-center justify-between gap-3 mb-2 min-h-[1.25rem] text-xs">
                <span v-if="stale && !previewing" class="inline-flex items-center gap-1 font-medium text-orange-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>有修改尚未体现在预览中
                </span>
                <span v-else-if="previewUrl && !previewing" class="text-gray-400">预览与当前表单一致</span>
                <span v-else />
                <a v-if="previewUrl" :href="previewUrl" target="_blank" rel="noopener"
                    class="font-medium text-primary-700 hover:underline">在新窗口打开</a>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-700 aspect-[297/420]">
                <iframe v-if="viewerSrc" :src="viewerSrc" title="日历预览" class="absolute inset-0 w-full h-full transition-opacity"
                    :class="previewing ? 'opacity-40' : 'opacity-100'"></iframe>
                <div v-else-if="!previewing && !previewError" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">
                    点击右上角「生成预览」查看效果
                </div>

                <div v-if="previewing" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/90 shadow text-sm text-primary-800">
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        正在生成预览...
                    </div>
                </div>

                <div v-if="previewError" class="absolute inset-x-3 bottom-3 p-3 text-xs text-red-800 rounded-xl bg-red-50 ring-1 ring-red-200">
                    {{ previewError }}
                </div>
            </div>
        </aside>
    </div>
</div>
</template>
