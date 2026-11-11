<script setup lang="ts">
import { computed, ref } from 'vue';
import type { SongRow } from '../types';

const props = defineProps<{ songs: SongRow[]; highlightId: string | null }>();
defineEmits<{ (e: 'open', id: string): void; (e: 'remove', song: SongRow): void }>();

const search = ref('');
const onlyUnchecked = ref(false);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.songs.filter((s) => {
        if (onlyUnchecked.value && s.checked) return false;
        if (!q) return true;
        return [s.name, s.band, s.album].some((v) => (v || '').toLowerCase().includes(q));
    });
});

const progress = computed(() =>
    props.songs.length ? Math.round((props.songs.filter((s) => s.checked).length / props.songs.length) * 100) : 0,
);

const shown = (v: string) => (v && v.toLowerCase() !== 'na' ? v : '');
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
            <input v-model="search" type="search" placeholder="搜索诗歌名称、乐队或专辑"
                class="flex-1 min-w-[12rem] h-10 px-3 rounded-xl border border-gray-200 bg-white text-sm outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" />
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 select-none">
                <input v-model="onlyUnchecked" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                只看未校对
            </label>
            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span class="tabular-nums">{{ filtered.length }} / {{ songs.length }} 首</span>
                <span class="inline-flex items-center gap-2" title="已校对的比例">
                    <span class="relative w-28 h-2 rounded-full bg-red-200 overflow-hidden">
                        <span class="absolute inset-y-0 left-0 rounded-full bg-green-600" :style="{ width: progress + '%' }"></span>
                    </span>
                    <span class="tabular-nums">校对 {{ progress }}%</span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">诗歌</th>
                        <th class="px-4 py-3 font-semibold hidden sm:table-cell">乐队 / 专辑</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">校对</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap hidden sm:table-cell" title="歌词内容被收集的次数">计数</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in filtered" :id="`song-${s.id}`" :key="s.id"
                        class="border-t border-gray-100 dark:border-gray-700 transition-colors"
                        :class="highlightId === s.id ? 'bg-primary-50 dark:bg-gray-700/60' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40'">
                        <td class="px-4 py-3">
                            <a :href="`/songs/${s.id}`" @click.prevent="$emit('open', s.id)"
                                class="font-semibold text-main dark:text-gray-100 hover:text-primary-700 dark:hover:text-primary-300">{{ s.name }}</a>
                            <div class="text-xs text-gray-400 sm:hidden">
                                {{ [shown(s.band), shown(s.album)].filter(Boolean).join(' | ') }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 hidden sm:table-cell">
                            {{ [shown(s.band), shown(s.album)].filter(Boolean).join(' | ') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span v-if="s.checked" class="inline-flex items-center gap-1 text-green-700">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.2 14.4-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4-6.4 6.4Z" /></svg>
                                是
                            </span>
                            <span v-else class="text-red-600">否</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 tabular-nums hidden sm:table-cell">{{ s.count || '' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a :href="`/songs/${s.id}`" @click.prevent="$emit('open', s.id)"
                                class="font-medium text-primary-700 hover:underline dark:text-primary-300">编辑</a>
                            <button type="button" class="ml-4 font-medium text-gray-400 hover:text-red-600 transition"
                                @click="$emit('remove', s)">删除</button>
                        </td>
                    </tr>
                    <tr v-if="!filtered.length">
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">没有符合条件的诗歌</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
