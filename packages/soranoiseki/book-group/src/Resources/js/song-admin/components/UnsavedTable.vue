<script setup lang="ts">
import { reactive } from 'vue';
import ChevronDownIcon from '../../icons/ChevronDownIcon.vue';
import type { UnsavedRow } from '../types';

defineProps<{ rows: UnsavedRow[] }>();
defineEmits<{ (e: 'upload', row: UnsavedRow): void }>();

const expanded = reactive<Record<number, boolean>>({});
const shown = (v: string | null) => (v && v.toLowerCase() !== 'na' ? v : '');
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 overflow-hidden">
        <p class="p-4 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
            这些歌词内容来自 PPT，但还没有进入诗歌库。点击名称可以查看内容，「上传」会把它加入诗歌库并打开编辑。
        </p>

        <div v-if="!rows.length" class="px-4 py-16 text-center text-sm text-gray-400">没有未保存的诗歌内容</div>

        <table v-else class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 font-semibold">诗歌</th>
                    <th class="px-4 py-3 font-semibold hidden sm:table-cell">乐队 / 专辑</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <template v-for="r in rows" :key="r.id">
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-2 font-semibold text-main dark:text-gray-100 hover:text-primary-700"
                                :aria-expanded="!!expanded[r.id]" @click="expanded[r.id] = !expanded[r.id]">
                                <ChevronDownIcon class="w-4 h-4 text-gray-400 transition-transform" :class="expanded[r.id] ? 'rotate-180' : ''" />
                                {{ r.name }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 hidden sm:table-cell">
                            {{ [shown(r.band), shown(r.album)].filter(Boolean).join(' | ') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="$emit('upload', r)"
                                class="h-8 px-4 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-sm hover:shadow transition">
                                上传
                            </button>
                        </td>
                    </tr>
                    <tr v-if="expanded[r.id]" class="bg-gray-50 dark:bg-gray-900">
                        <td colspan="3" class="px-4 py-3">
                            <textarea readonly rows="16" :value="r.text"
                                class="w-full rounded-xl border border-gray-200 bg-white p-4 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"></textarea>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </section>
</template>
