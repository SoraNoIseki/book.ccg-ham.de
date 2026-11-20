<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import BottomSheet from '../../shared/ui/BottomSheet.vue';
import MessageList from '../../shared/ui/MessageList.vue';
import { clamp, cropGeometry, DEFAULT_CROP, MAX_ZOOM, useElementWidth, type Crop } from '../cropGeometry';

// Choose which part of a monthly photo shows in the header. The frame has the proportions of the header of the
// design this calendar uses and shows the photo with the overlay and the verse, so the result can be judged.
// Drag the photo to move it, zoom with the slider (or the keys: arrows move, + and - zoom).
const props = defineProps<{ month: number | null }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const cal = useCalendarStore();
const { images, crops, headerPreview, details, errors, isImporting } = storeToRefs(cal);

/** Kept while the sheet fades out, so that the content does not change */
const shown = ref(1);
const slot = computed(() => String(shown.value).padStart(2, '0'));
const info = computed(() => images.value[slot.value]);
const ratio = computed(() => headerPreview.value?.ratio ?? 0.5);

const crop = ref<Crop>({ ...DEFAULT_CROP });
const touched = ref(false);
const nat = ref({ w: 0, h: 0 });
const showOverlay = ref(true);
const showVerse = ref(true);

const frame = ref<HTMLElement | null>(null);
const frameW = useElementWidth(frame);
const g = cropGeometry(frameW, ratio, nat, crop);

watch(() => props.month, (m) => {
    if (m === null) return;
    cal.clearMessages();
    shown.value = m;
    crop.value = { ...(crops.value[String(m).padStart(2, '0')] ?? DEFAULT_CROP) };
    touched.value = false;
    nat.value = { w: 0, h: 0 };
}, { immediate: true });

function onLoad(e: Event): void {
    const img = e.target as HTMLImageElement;
    nat.value = { w: img.naturalWidth, h: img.naturalHeight };
}

// ---- moving and zooming ----
function setCentreFromCorner(left: number, top: number): void {
    const { w, h } = nat.value;
    const l = clamp(left, 0, w - g.sourceW.value);
    const t = clamp(top, 0, h - g.sourceH.value);
    crop.value = { ...crop.value, x: (l + g.sourceW.value / 2) / w, y: (t + g.sourceH.value / 2) / h };
    touched.value = true;
}

function setZoom(zoom: number): void {
    const z = clamp(Math.round(zoom * 100) / 100, 1, MAX_ZOOM);
    // zooming keeps the centre that is shown now
    crop.value = g.ready.value ? { x: g.centre.value.x, y: g.centre.value.y, zoom: z } : { ...crop.value, zoom: z };
    touched.value = true;
}

let drag: { px: number; py: number; left: number; top: number } | null = null;

function onDown(e: PointerEvent): void {
    if (!g.ready.value) return;
    (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
    drag = { px: e.clientX, py: e.clientY, left: g.left.value, top: g.top.value };
}

function onMove(e: PointerEvent): void {
    if (!drag) return;
    const s = g.scale.value;
    setCentreFromCorner(drag.left - (e.clientX - drag.px) / s, drag.top - (e.clientY - drag.py) / s);
}

function onUp(): void {
    drag = null;
}

function onKey(e: KeyboardEvent): void {
    if (!g.ready.value) return;
    const stepX = g.sourceW.value * 0.04;
    const stepY = g.sourceH.value * 0.04;
    const move: Record<string, [number, number]> = { ArrowLeft: [-stepX, 0], ArrowRight: [stepX, 0], ArrowUp: [0, -stepY], ArrowDown: [0, stepY] };
    if (move[e.key]) {
        e.preventDefault();
        setCentreFromCorner(g.left.value + move[e.key][0], g.top.value + move[e.key][1]);
    } else if (e.key === '+' || e.key === '=') {
        e.preventDefault();
        setZoom(crop.value.zoom + 0.1);
    } else if (e.key === '-') {
        e.preventDefault();
        setZoom(crop.value.zoom - 0.1);
    }
}

function reset(): void {
    crop.value = { ...DEFAULT_CROP };
    touched.value = true;
}

async function save(): Promise<void> {
    const c = g.ready.value ? g.centre.value : crop.value;
    const ok = await cal.saveCrop(slot.value, { x: round(c.x), y: round(c.y), zoom: crop.value.zoom });
    if (ok) emit('close');
}

const round = (n: number): number => Math.round(n * 10000) / 10000;

// ---- overlay and verse, drawn the way the stylesheet of the theme does (approximately) ----
const overlayUrl = computed(() => (showOverlay.value && headerPreview.value?.showOverlay && images.value.overlay?.exists ? images.value.overlay.url : null));
const detail = computed(() => details.value.find((d) => d.month === shown.value));
const verseLines = computed(() => (detail.value?.bible_text ?? '').split('|').map((l) => l.trim()).filter(Boolean));
const unit = computed(() => frameW.value * (headerPreview.value?.remRatio ?? 0.01));

const verseBox = computed(() => {
    const v = headerPreview.value?.verse;
    if (!v) return {};
    const u = unit.value;
    return {
        padding: v.padding.map((p) => `${p * u}px`).join(' '),
        color: v.color,
        fontSize: `${v.size * u}px`,
        lineHeight: String(v.lineHeight),
        background: v.gradient ? 'linear-gradient(to top, rgba(10,22,45,0.78) 0%, rgba(10,22,45,0.5) 55%, rgba(10,22,45,0) 100%)' : undefined,
    };
});
const verseSource = computed(() => {
    const v = headerPreview.value?.verse;
    if (!v) return {};
    return { marginTop: `${v.sourceGap * unit.value}px`, fontSize: `${v.sourceSize * unit.value}px`, letterSpacing: `${0.1 * unit.value}px`, opacity: 0.85 };
});

// Position of the visible part in the whole photo (small map below the frame)
const mapRect = computed(() => {
    if (!g.ready.value) return null;
    const { w, h } = nat.value;
    return {
        left: `${(g.left.value / w) * 100}%`,
        top: `${(g.top.value / h) * 100}%`,
        width: `${(g.sourceW.value / w) * 100}%`,
        height: `${(g.sourceH.value / h) * 100}%`,
    };
});

const btn = 'h-12 rounded-xl text-sm font-semibold active:scale-95 transition';
</script>

<template>
    <BottomSheet :open="month !== null" :title="`调整 ${shown} 月图片的显示范围`" wide @close="emit('close')">
        <div class="space-y-4 pt-1">
            <MessageList :errors="errors" />

            <!-- The header as the calendar shows it -->
            <div ref="frame" tabindex="0" role="group" aria-label="拖动图片调整显示范围，方向键移动，加号减号缩放"
                class="relative w-full overflow-hidden rounded-xl bg-gray-200 dark:bg-gray-900 ring-1 ring-gray-300 dark:ring-gray-600 select-none touch-none cursor-grab active:cursor-grabbing outline-none focus-visible:ring-2 focus-visible:ring-primary-600"
                :style="{ aspectRatio: `1 / ${ratio}` }"
                @pointerdown="onDown" @pointermove="onMove" @pointerup="onUp" @pointercancel="onUp" @keydown="onKey">
                <img v-if="info?.url" :key="info.url" :src="info.url" alt="" draggable="false"
                    class="absolute left-0 top-0 max-w-none pointer-events-none" :style="g.imgStyle.value" @load="onLoad" />
                <img v-if="overlayUrl" :src="overlayUrl" alt="" draggable="false"
                    class="absolute inset-0 w-full h-full object-contain pointer-events-none" />
                <div v-if="showVerse && verseLines.length" class="absolute inset-x-0 bottom-0 text-center pointer-events-none" :style="verseBox">
                    <div><template v-for="(line, i) in verseLines" :key="i">{{ line }}<br v-if="i < verseLines.length - 1" /></template></div>
                    <div v-if="detail?.bible_source" :style="verseSource">{{ detail.bible_source }}</div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-start">
                <div class="space-y-3">
                    <label class="block text-xs text-gray-500">
                        缩放 <span class="font-semibold text-main dark:text-gray-100 tabular-nums">{{ crop.zoom.toFixed(2) }}×</span>
                        <input type="range" min="1" :max="MAX_ZOOM" step="0.01" :value="crop.zoom"
                            class="block w-full mt-2 accent-primary-600"
                            @input="setZoom(Number(($event.target as HTMLInputElement).value))" />
                    </label>
                    <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <label class="inline-flex items-center gap-2">
                            <input v-model="showVerse" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            显示经文
                        </label>
                        <label v-if="images.overlay?.exists && headerPreview?.showOverlay" class="inline-flex items-center gap-2">
                            <input v-model="showOverlay" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            显示叠加层
                        </label>
                    </div>
                    <p class="text-xs text-gray-400">
                        在图片上拖动来移动位置。经文按当前外观的字号和颜色示意，字体和精确位置以生成的 PDF 为准。
                    </p>
                </div>

                <!-- Whole photo with the visible part marked -->
                <div v-if="info?.url" class="sm:w-48">
                    <div class="relative inline-block max-w-full overflow-hidden rounded-lg ring-1 ring-gray-300 dark:ring-gray-600">
                        <img :src="info.url" alt="" draggable="false" class="block max-h-36 max-w-full" />
                        <div v-if="mapRect" class="absolute ring-2 ring-white pointer-events-none" :style="{ ...mapRect, boxShadow: '0 0 0 999px rgba(0,0,0,0.5)' }"></div>
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">整张图片，亮出的是显示的部分</p>
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="button" :class="btn + ' px-4 text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40'" @click="reset">还原居中</button>
                <button type="button" :class="btn + ' px-5 ml-auto text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600'" @click="emit('close')">取消</button>
                <button type="button" :disabled="!touched || isImporting" @click="save"
                    :class="btn + ' px-8 text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50'">
                    {{ isImporting ? '保存中...' : '保存' }}
                </button>
            </div>
        </div>
    </BottomSheet>
</template>
