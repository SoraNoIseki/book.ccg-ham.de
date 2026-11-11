<script setup lang="ts">
import { ref } from 'vue';
import type { ImageInfo } from '../types';

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
}>();

const emit = defineEmits<{
    (e: 'upload', file: File): void;
    (e: 'remove'): void;
    (e: 'view', url: string): void;
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
    <div class="group rounded-2xl bg-white dark:bg-gray-800 ring-1 transition"
        :class="dragging ? 'ring-primary-500 ring-2 bg-primary-100/40' : 'ring-gray-200 dark:ring-gray-700'"
        @dragover.prevent="!readonly && (dragging = true)" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
        <!-- Preview -->
        <div class="relative overflow-hidden rounded-t-2xl bg-gray-100 dark:bg-gray-900" :class="aspect ?? 'aspect-[2/1]'"
            :style="fit === 'contain' && info?.exists ? checker : undefined">
            <button v-if="info?.exists && info.url" type="button" class="absolute inset-0 w-full h-full cursor-zoom-in"
                :title="'查看大图：' + label" @click="emit('view', info.url!)">
                <img :src="info.url" :alt="label" loading="lazy" class="w-full h-full"
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

        <!-- Footer: name, status, actions -->
        <div class="px-3 py-2.5">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-main dark:text-gray-100 truncate">{{ label }}</div>
                    <div class="text-xs mt-0.5" :class="info?.exists ? 'text-green-700' : required ? 'text-orange-600' : 'text-gray-400'">
                        <template v-if="info?.exists">已上传 · {{ formatSize(info.size) }}</template>
                        <template v-else-if="readonly">未上传</template>
                        <template v-else>{{ required ? '未上传（必需）' : '未上传（可选）' }}</template>
                    </div>
                </div>

                <div v-if="!readonly" class="flex items-center gap-1 shrink-0">
                    <button type="button" :disabled="busy" @click="pick"
                        class="h-8 px-3 rounded-lg text-xs font-semibold text-primary-800 bg-primary-100/70 hover:bg-primary-100 dark:text-primary-200 dark:bg-primary-900/50 dark:hover:bg-primary-900/80 disabled:opacity-50 transition">
                        {{ info?.exists ? '替换' : '上传' }}
                    </button>
                    <button v-if="info?.exists" type="button" :disabled="busy" title="删除"
                        @click="emit('remove')"
                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 disabled:opacity-50 transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6" />
                        </svg>
                    </button>
                </div>
            </div>
            <p v-if="hint" class="mt-1 text-[11px] leading-snug text-gray-400">{{ hint }}</p>
        </div>

        <input ref="input" type="file" class="hidden" accept="image/jpeg,image/png" @change="onFile" />
    </div>
</template>
