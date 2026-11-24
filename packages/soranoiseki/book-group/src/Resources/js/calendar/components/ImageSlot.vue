<script setup lang="ts">
import { ref } from 'vue';
import type { ImageCrop, ImageInfo } from '../types';
import CroppedImage from './CroppedImage.vue';

const props = defineProps<{
    label: string;
    hint?: string;
    info?: ImageInfo;
    busy?: boolean;
    required?: boolean;
    /** History versions: look only, no upload, replace or delete */
    readonly?: boolean;
    /** Tailwind size class of the preview box (aspect-* or h-*) */
    aspect?: string;
    /** cover = fill and crop; contain = show the whole image (wide or transparent artwork) */
    fit?: 'cover' | 'contain';
    /** Monthly photos: height / width of the header. The preview then shows the part that is really used. */
    ratio?: number;
    /** The chosen part of the photo (centred when empty) */
    crop?: ImageCrop | null;
    /** Small reference text under the name (monthly photos: the verse of that month) */
    note?: string;
    /** Rearranging: the card only shows a swap button instead of the upload actions */
    swappable?: boolean;
    /** This card is the first one chosen for a swap */
    picked?: boolean;
    /** Rearranging: where the photo of this card was before (shown when it moved) */
    movedFrom?: string;
}>();

const emit = defineEmits<{
    (e: 'upload', file: File): void;
    (e: 'remove'): void;
    (e: 'view', url: string): void;
    (e: 'crop'): void;
    (e: 'swap'): void;
}>();

const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

function pick(): void {
    if (!props.busy && !props.readonly) input.value?.click();
}

function onFile(e: Event): void {
    const el = e.target as HTMLInputElement;
    const file = el.files?.[0];
    if (file) emit('upload', file);
    el.value = ''; // allow selecting the same file again
}

function onDrop(e: DragEvent): void {
    dragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file && !props.busy && !props.readonly) emit('upload', file);
}

// Light checkerboard so transparent PNGs (overlay) are visible
const checker = {
    backgroundImage: 'conic-gradient(#e5e7eb 25%, #f3f4f6 0 50%, #e5e7eb 0 75%, #f3f4f6 0)',
    backgroundSize: '16px 16px',
};

function formatSize(bytes: number | null | undefined): string {
    if (!bytes) return '';
    return bytes > 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.round(bytes / 1024)} KB`;
}
</script>

<template>
    <div class="group flex flex-col rounded-2xl bg-white dark:bg-gray-800 ring-1 transition"
        :class="dragging ? 'ring-primary-500 ring-2 bg-primary-100/40' : picked ? 'ring-primary-600 ring-2' : movedFrom ? 'ring-amber-400 dark:ring-amber-500' : 'ring-gray-200 dark:ring-gray-700'"
        @dragover.prevent="!readonly && (dragging = true)" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
        <!-- Preview -->
        <div class="relative overflow-hidden rounded-t-2xl bg-gray-100 dark:bg-gray-900" :class="ratio ? '' : (aspect ?? 'aspect-[2/1]')"
            :style="[ratio ? { aspectRatio: `1 / ${ratio}` } : {}, fit === 'contain' && info?.exists ? checker : {}]">
            <button v-if="info?.exists && info.url" type="button" class="absolute inset-0 w-full h-full cursor-zoom-in"
                :title="'查看大图：' + label" @click="emit('view', info.preview ?? info.url!)">
                <CroppedImage v-if="ratio" :src="info.thumb ?? info.url" :ratio="ratio" :crop="crop" :alt="label" class="absolute inset-0" />
                <img v-else :src="info.thumb ?? info.url" :alt="label" loading="lazy" class="w-full h-full"
                    :class="fit === 'contain' ? 'object-contain' : 'object-cover'" />
            </button>

            <button v-else type="button" @click="pick" :disabled="readonly" :class="readonly ? 'cursor-default' : 'hover:text-primary-700 dark:hover:text-primary-300'"
                class="absolute inset-0 w-full h-full flex flex-col items-center justify-center gap-2 text-gray-400 transition">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="9" cy="9" r="1.5" />
                    <path d="m21 15-5-5L5 21" />
                </svg>
                <span class="text-xs font-medium">{{ readonly ? '未上传' : '点击或拖入图片' }}</span>
            </button>

            <!-- Uploading overlay -->
            <div v-if="busy" class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70">
                <svg class="w-6 h-6 animate-spin text-primary-700 dark:text-primary-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
            </div>
        </div>

        <!-- Footer: name + status, actions, reference text at the bottom -->
        <div class="flex flex-1 flex-col gap-2 px-3 py-2.5">
            <div class="flex items-baseline justify-between gap-2">
                <div class="text-sm font-semibold text-main dark:text-gray-100 truncate">{{ label }}</div>
                <div class="text-xs text-right" :class="info?.exists ? 'text-green-700' : required ? 'text-orange-600' : 'text-gray-400'">
                    <template v-if="info?.exists">已上传 · {{ formatSize(info.size) }}<template v-if="crop"> · 已调整范围</template></template>
                    <template v-else-if="readonly">未上传</template>
                    <template v-else>{{ required ? '未上传（必需）' : '未上传（可选）' }}</template>
                </div>
            </div>
            <div v-if="movedFrom" class="text-xs text-amber-700 dark:text-amber-400">{{ movedFrom }}</div>

            <button v-if="swappable" type="button" :aria-pressed="picked" :aria-label="`对调 ${label}`" @click="emit('swap')"
                :class="picked ? 'bg-primary-600 text-white' : 'text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40 hover:bg-primary-100/60 dark:hover:bg-gray-700'"
                class="h-8 px-3 self-end rounded-lg text-xs font-semibold whitespace-nowrap active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-primary-600">对调</button>

            <div v-else-if="!readonly" class="flex items-center justify-end gap-1">
                <button type="button" :disabled="busy" @click="pick"
                    class="h-8 px-3 rounded-lg text-xs font-semibold text-primary-800 bg-primary-100/70 hover:bg-primary-100 dark:text-primary-200 dark:bg-primary-900/50 dark:hover:bg-primary-900/80 disabled:opacity-50 transition">
                    {{ info?.exists ? '替换' : '上传' }}
                </button>
                <button v-if="ratio && info?.exists" type="button" :disabled="busy" title="调整显示范围" aria-label="调整显示范围"
                    @click="emit('crop')"
                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-primary-800 dark:text-primary-300 hover:bg-primary-100/70 dark:hover:bg-primary-900/50 disabled:opacity-50 transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2v14a2 2 0 0 0 2 2h14M2 6h14a2 2 0 0 1 2 2v14" />
                    </svg>
                </button>
                <button v-if="info?.exists" type="button" :disabled="busy" title="删除" aria-label="删除"
                    @click="emit('remove')"
                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 disabled:opacity-50 transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6" />
                    </svg>
                </button>
            </div>

            <p v-if="hint" class="text-[11px] leading-snug text-gray-400">{{ hint }}</p>
            <p v-if="note" class="mt-auto text-[11px] leading-snug text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ note }}</p>
        </div>

        <input ref="input" type="file" class="hidden" accept="image/jpeg,image/png" @change="onFile" />
    </div>
</template>
