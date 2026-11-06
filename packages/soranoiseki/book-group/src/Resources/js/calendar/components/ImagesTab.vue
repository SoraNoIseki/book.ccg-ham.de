<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { MONTH_SLOTS, REQUIRED_SLOTS, useCalendarStore } from '../store/calendar';
import ImageSlot from './ImageSlot.vue';
import AlertBox from '../../ppt/components/ui/AlertBox.vue';

const cal = useCalendarStore();
const { images, busySlots, year, requiredImagesDone, editable } = storeToRefs(cal);

const bulkInput = ref<HTMLInputElement | null>(null);
const lightbox = ref<string | null>(null);

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
            <h3 class="mb-3 text-sm font-bold text-main dark:text-gray-100">每月图片</h3>
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-3 lg:grid-cols-4">
                <ImageSlot :readonly="!editable" v-for="slot in MONTH_SLOTS" :key="slot" :label="slotLabel(slot)" :hint="slot === '01' ? '横版 2:1（宽是高的两倍），会按此比例裁切。' : undefined"
                    :info="images[slot]" :busy="busySlots[slot]" :required="REQUIRED_SLOTS.includes(slot)"
                    @upload="upload(slot, $event)" @remove="remove(slot)" @view="lightbox = $event" />
            </div>
        </section>

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
