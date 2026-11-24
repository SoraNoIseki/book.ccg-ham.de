<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { MONTH_SLOTS, REQUIRED_SLOTS, useCalendarStore } from '../store/calendar';
import ImageSlot from './ImageSlot.vue';
import CropSheet from './CropSheet.vue';
import AlertBox from '../../ppt/components/ui/AlertBox.vue';

const cal = useCalendarStore();
const { images, crops, headerPreview, busySlots, year, requiredImagesDone, editable, details, isImporting } = storeToRefs(cal);

const bulkInput = ref<HTMLInputElement | null>(null);
const lightbox = ref<string | null>(null);
/** Month whose display range is being adjusted */
const cropping = ref<number | null>(null);

// ---- single upload / delete ----
async function upload(slot: string, file: File): Promise<void> {
    if (await cal.uploadImage(slot, file)) {
        cal.successMessage = `已上传：${slotLabel(slot)}`;
    }
}

async function remove(slot: string): Promise<void> {
    if (confirm(`确认要删除「${slotLabel(slot)}」吗？`)) await cal.deleteImage(slot);
}

function slotLabel(slot: string): string {
    if (slot === 'cover') return '封面';
    if (slot === 'overlay') return '叠加层';
    if (slot === 'footer') return '页脚';
    return `${Number(slot)} 月`;
}

// ---- bulk upload: assign files to slots by file name (cover, 1..12 / 01..12, overlay, footer) ----
function slotFromName(fileName: string): string | null {
    const base = fileName.replace(/\.[^.]+$/, '').trim().toLowerCase();
    if (['cover', 'overlay', 'footer'].includes(base)) return base;
    if (/^\d{1,2}$/.test(base)) {
        const slot = base.padStart(2, '0');
        return MONTH_SLOTS.includes(slot) ? slot : null;
    }
    return null;
}

async function onBulk(e: Event): Promise<void> {
    const el = e.target as HTMLInputElement;
    const files = Array.from(el.files ?? []);
    el.value = '';
    if (!files.length) return;

    cal.clearMessages();
    const skipped: string[] = [];
    let ok = 0;

    for (const file of files) {
        const slot = slotFromName(file.name);
        if (!slot) {
            skipped.push(file.name);
            continue;
        }
        if (await cal.uploadImage(slot, file, true)) ok++;
    }

    if (skipped.length) {
        cal.errors = [...cal.errors, `以下文件名无法识别，已跳过：${skipped.join('、')}（请命名为 cover、01 至 12、overlay、footer）`];
    }
    if (ok) cal.successMessage = `已上传 ${ok} 张图片。`;
}

/** The header ratio of the design (height / width), which decides how the photos are cut */
const monthHint = computed(() => {
    const r = headerPreview.value?.ratio ?? 0.5;
    return `横版，高度是宽度的 ${r.toFixed(2)} 倍（当前外观设定），图片按此比例显示。` + (editable.value ? '点卡片下方的裁切图标可以调整显示的范围。' : '');
});

/** Verse of a month, shown small under its photo for reference (line breaks are "|" in the data) */
function verseOf(month: number): string {
    const d = details.value.find((x) => x.month === month);
    if (!d?.bible_text) return '';
    return d.bible_text.replace(/\|/g, ' ') + (d.bible_source ? `（${d.bible_source}）` : '');
}

// --- Rearranging -----------------------------------------------------------------------------
// The month labels stay where they are; the photos, the verses or both are swapped (the two boxes).
// Changes are made on a draft and saved at once.
const moveImages = ref(true);
const moveVerses = ref(true);
const draft = ref<number[] | null>(null);
const ordering = computed(() => draft.value !== null);
const order = computed<number[]>(() => draft.value ?? MONTH_SLOTS.map(Number));
const changed = computed(() => (moveImages.value || moveVerses.value) && !!draft.value?.some((from, i) => from !== i + 1));
/** Index of the card chosen for a swap, waiting for the second one */
const swapFrom = ref<number | null>(null);
const canOrder = computed(() => MONTH_SLOTS.some((s) => images.value[s]?.exists) || cal.monthDetailsDone > 0);
/** What moves, for the texts: 图片 / 经文 / 图片和经文 */
const noun = computed(() => [moveImages.value && '图片', moveVerses.value && '经文'].filter(Boolean).join('和'));

/** Month of the photo / of the verse shown on the card of month i + 1 */
const imageMonth = (i: number): number => (ordering.value && moveImages.value ? order.value[i] : i + 1);
const verseMonth = (i: number): number => (ordering.value && moveVerses.value ? order.value[i] : i + 1);

function startOrdering(): void {
    cal.clearMessages();
    swapFrom.value = null;
    draft.value = MONTH_SLOTS.map(Number);
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
    if (!draft.value || !changed.value) return;
    const order = [...draft.value];
    // Photos first; if that fails nothing is changed, so the draft stays for another try
    if (moveImages.value && !(await cal.reorderImages(order))) return;
    if (moveVerses.value && !(await cal.reorderBibleTexts(order))) return;
    cal.successMessage = `${noun.value}顺序已保存。`;
    stopOrdering();
}

// A draft belongs to one calendar: drop it when another year is shown
watch(year, stopOrdering);

function onKey(e: KeyboardEvent): void {
    if (e.key === 'Escape') lightbox.value = null;
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="space-y-8">
        <!-- Intro + bulk upload -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-3 max-w-2xl">
                <p v-if="editable" class="text-sm text-gray-600 dark:text-gray-300">
                    {{ year }} 年日历共需要 <b>封面 + 12 张月份图片</b>（已完成 {{ requiredImagesDone }}/13），
                    叠加层和页脚为可选。点击预览图可查看大图，也可以直接把图片拖到对应卡片上。
                </p>
                <p v-else class="text-sm text-gray-600 dark:text-gray-300">
                    历史版本只能查看已上传的图片（{{ requiredImagesDone }}/13），点击预览图可查看大图。
                </p>
                <AlertBox v-if="editable" type="tip" title="批量上传">
                    一次选择多张图片，系统会按文件名自动归位：cover、01 至 12、overlay、footer（例如 03.jpg 会放到 3 月）。
                </AlertBox>
            </div>
            <div v-if="editable">
                <button type="button" @click="bulkInput?.click()"
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" />
                    </svg>
                    批量上传
                </button>
                <input ref="bulkInput" type="file" multiple class="hidden" accept="image/jpeg,image/png" @change="onBulk" />
            </div>
        </div>

        <!-- Cover + extras -->
        <section>
            <h3 class="mb-3 text-sm font-bold text-main dark:text-gray-100">封面与装饰</h3>
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-3 lg:grid-cols-4">
                <ImageSlot :readonly="!editable" label="封面" aspect="h-64" fit="cover" hint="竖版，建议与 A3 同比例（297:420）。JPG 或 PNG。" required
                    :info="images.cover" :busy="busySlots.cover" @upload="upload('cover', $event)" @remove="remove('cover')"
                    @view="lightbox = $event" />
                <ImageSlot :readonly="!editable" label="叠加层" aspect="h-64" fit="contain"
                    hint="透明 PNG，盖在每月图片上方（例如渐变遮罩，让经文更清晰）。" :info="images.overlay"
                    :busy="busySlots.overlay" @upload="upload('overlay', $event)" @remove="remove('overlay')"
                    @view="lightbox = $event" />
                <ImageSlot :readonly="!editable" label="页脚" aspect="h-64" fit="contain" hint="每页底部的教会标识和联系方式，宽图。"
                    :info="images.footer" :busy="busySlots.footer" @upload="upload('footer', $event)"
                    @remove="remove('footer')" @view="lightbox = $event" />
            </div>
        </section>

        <!-- Monthly photos -->
        <section>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-main dark:text-gray-100">每月图片</h3>
                <div v-if="ordering" class="flex gap-2">
                    <button type="button" @click="stopOrdering"
                        class="h-9 px-4 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 active:scale-95 transition">取消</button>
                    <button type="button" :disabled="!changed || isImporting" @click="saveOrder"
                        class="h-9 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                        {{ isImporting ? '保存中...' : '保存顺序' }}
                    </button>
                </div>
                <button v-else-if="editable" type="button" :disabled="!canOrder" @click="startOrdering"
                    class="inline-flex items-center gap-2 whitespace-nowrap h-9 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 transition disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 4v16M7 4 3.5 7.5M7 4l3.5 3.5M17 20V4M17 20l-3.5-3.5M17 20l3.5-3.5" />
                    </svg>
                    调整顺序
                </button>
            </div>
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">{{ monthHint }}</p>
            <div v-if="ordering" class="mb-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1">
                    <span>要对调的内容：</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer"><input v-model="moveImages" type="checkbox" class="rounded accent-primary-600" />图片</label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer"><input v-model="moveVerses" type="checkbox" class="rounded accent-primary-600" />经文</label>
                </div>
                <p v-if="!moveImages && !moveVerses" class="text-orange-600">请至少选择一项。</p>
                <p v-else-if="swapFrom !== null">请选择要与 <b>{{ swapFrom + 1 }} 月</b> 对调的月份（再点一次取消）。</p>
                <p v-else>点一个月份的「对调」，再点另一个月份的「对调」，两个月的{{ noun }}就会互换。改完点「保存顺序」。</p>
            </div>
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-3 lg:grid-cols-4">
                <ImageSlot v-for="(_, i) in order" :key="MONTH_SLOTS[i]" :readonly="!editable || ordering" :label="slotLabel(MONTH_SLOTS[i])"
                    :ratio="headerPreview?.ratio" :crop="crops[MONTH_SLOTS[imageMonth(i) - 1]]" :info="images[MONTH_SLOTS[imageMonth(i) - 1]]"
                    :busy="busySlots[MONTH_SLOTS[i]]" :required="REQUIRED_SLOTS.includes(MONTH_SLOTS[i])" :note="verseOf(verseMonth(i))"
                    :swappable="ordering && (moveImages || moveVerses)" :picked="swapFrom === i" :moved-from="ordering && order[i] !== i + 1 ? `${order[i]} 月（${noun}）` : undefined"
                    @upload="upload(MONTH_SLOTS[i], $event)" @remove="remove(MONTH_SLOTS[i])" @view="lightbox = $event"
                    @crop="cropping = i + 1" @swap="swap(i)" />
            </div>
        </section>

        <CropSheet :month="cropping" @close="cropping = null" />

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightbox" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 cursor-zoom-out"
                role="dialog" aria-modal="true" @click="lightbox = null">
                <img :src="lightbox" alt="" class="max-w-full max-h-full rounded-lg shadow-2xl" />
                <button type="button" aria-label="关闭"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/90 text-gray-700 text-xl leading-none hover:bg-white"
                    @click.stop="lightbox = null">×</button>
            </div>
        </Teleport>
    </div>
</template>
