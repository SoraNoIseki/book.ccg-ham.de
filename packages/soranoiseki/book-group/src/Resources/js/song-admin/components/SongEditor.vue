<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import { extractErrors } from '../../shared/http';
import { beautifySongText } from '../../shared/songText';
import type { SongContentRow, SongDetail } from '../types';

const props = defineProps<{ songId: string }>();
const emit = defineEmits<{
    (e: 'back', id: string): void;
    (e: 'saved', message: string): void;
    (e: 'error', messages: string[]): void;
}>();

const song = ref<SongDetail | null>(null);
const contents = ref<SongContentRow[]>([]);
const text = ref('');
const checked = ref(false);
const corrected = ref(false);
const loading = ref(true);
const saving = ref(false);

// What is stored, to tell whether there are unsaved edits
const saved = ref({ text: '', checked: false, corrected: false });
const dirty = computed(
    () => text.value !== saved.value.text || checked.value !== saved.value.checked || corrected.value !== saved.value.corrected,
);

function apply(data: { song: SongDetail; contents: SongContentRow[] }): void {
    song.value = data.song;
    contents.value = data.contents;
    text.value = data.song.text || '';
    checked.value = data.song.checked;
    corrected.value = data.song.corrected;
    saved.value = { text: text.value, checked: checked.value, corrected: corrected.value };
}

async function load(): Promise<void> {
    try {
        const { data } = await axios.get(`/api/songs-admin/${props.songId}`);
        apply(data);
    } catch (e) {
        emit('error', await extractErrors(e, '无法加载这首诗歌。'));
    } finally {
        loading.value = false;
    }
}

async function save(): Promise<void> {
    if (saving.value) return;
    saving.value = true;
    try {
        const { data } = await axios.put(`/api/songs-admin/${props.songId}`, {
            text: text.value,
            checked: checked.value,
            corrected: corrected.value,
        });
        apply(data);
        emit('saved', data.message || '已保存');
    } catch (e) {
        emit('error', await extractErrors(e, '保存失败。'));
    } finally {
        saving.value = false;
    }
}

function back(): void {
    if (dirty.value && !confirm('有未保存的修改，确认要离开吗？')) return;
    saved.value = { text: text.value, checked: checked.value, corrected: corrected.value };
    emit('back', props.songId);
}

// Warn before closing the tab with unsaved edits
function beforeUnload(e: BeforeUnloadEvent): void {
    if (dirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
}

onMounted(() => {
    load();
    window.addEventListener('beforeunload', beforeUnload);
});
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload));
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <button type="button" @click="back"
                class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl text-sm font-semibold text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-600">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                返回诗歌列表
            </button>

            <div class="flex items-center gap-3">
                <span v-if="dirty" class="text-xs font-medium text-orange-600">有未保存的修改</span>
                <button type="button" :disabled="!dirty || saving || loading" @click="save"
                    class="inline-flex items-center h-10 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg transition disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed">
                    {{ saving ? '保存中...' : '保存' }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="py-16 text-center text-sm text-gray-400">加载中...</div>

        <template v-else-if="song">
            <section class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 p-5 mb-6">
                <h2 class="text-xl font-semibold text-main dark:text-gray-100 mb-3">{{ song.name }}</h2>
                <dl class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-gray-400">乐队</dt><dd class="text-gray-700 dark:text-gray-200">{{ song.band }}</dd>
                    <dt class="text-gray-400">专辑</dt><dd class="text-gray-700 dark:text-gray-200">{{ song.album }}</dd>
                    <dt class="text-gray-400">ID</dt><dd class="text-gray-500 break-all">{{ song.group_id }}</dd>
                </dl>
                <p class="mt-3 text-xs text-gray-400">名称、乐队和专辑取自歌词的前两行（第二行写成「乐队《专辑》」），保存后会同步更新。</p>

                <div class="mt-4 flex flex-wrap gap-6">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold select-none">
                        <input v-model="checked" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        校对完成
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-semibold select-none">
                        <input v-model="corrected" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        已验收
                    </label>
                </div>
            </section>

            <div class="grid gap-6" :class="contents.length ? 'lg:grid-cols-2' : ''">
                <section>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-bold text-main dark:text-gray-100">歌词</h3>
                        <button type="button" @click="text = beautifySongText(text)"
                            class="h-8 px-3 rounded-lg text-xs font-semibold text-primary-800 bg-primary-50 hover:bg-primary-100 transition">
                            自动优化格式
                        </button>
                    </div>
                    <textarea v-model="text" rows="32"
                        class="w-full rounded-xl border border-gray-300 bg-white p-4 text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100"></textarea>
                </section>

                <section v-if="contents.length">
                    <h3 class="mb-2 text-sm font-bold text-main dark:text-gray-100">PPT 中收集到的内容（{{ contents.length }}）</h3>
                    <div v-for="c in contents" :key="c.id" class="mb-5">
                        <button type="button" @click="text = c.text"
                            class="mb-2 w-full h-9 rounded-lg text-xs font-semibold text-primary-800 bg-primary-50 hover:bg-primary-100 transition">
                            应用到左边
                        </button>
                        <textarea readonly rows="14" :value="c.text"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"></textarea>
                    </div>
                </section>
            </div>
        </template>
    </div>
</template>
