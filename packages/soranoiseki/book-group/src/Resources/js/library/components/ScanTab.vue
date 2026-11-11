<script setup lang="ts">
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import MemberPicker from './MemberPicker.vue';

const lib = useLibraryStore();
const { activity, isBusy } = storeToRefs(lib);
const manual = ref('');

function submitManual(): void {
    const code = manual.value.trim();
    if (!code) return;
    manual.value = '';
    lib.lookup(code);
}

const icon: Record<string, string> = { borrow: 'bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-200', return: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300', renew: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' };
const label: Record<string, string> = { borrow: '借出', return: '归还', renew: '续借' };
</script>

<template>
    <div class="space-y-6">
        <!-- Big scan button -->
        <div class="text-center">
            <button type="button" @click="lib.openScanner()"
                class="w-full sm:w-auto sm:min-w-72 inline-flex flex-col items-center justify-center gap-2 py-8 px-10 rounded-3xl text-white bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-700/30 hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
                <svg class="w-14 h-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 8v8M11 8v8M15 8v8M18 8v8" />
                </svg>
                <span class="text-lg font-bold">扫描条形码</span>
                <span class="text-sm text-white/80">书背的 ISBN 或书上的标签</span>
            </button>
            <p class="mt-3 text-sm text-gray-500">借书：扫码 → 选借阅人 → 借出。还书：扫码 → 归还。</p>
        </div>

        <!-- Optional: pick the borrower first, then lend several books in a row -->
        <section aria-labelledby="borrower-title" class="space-y-2">
            <h2 id="borrower-title" class="text-sm font-semibold text-gray-500">一次借多本书？先选好借阅人</h2>
            <MemberPicker />
        </section>

        <!-- Manual entry -->
        <form @submit.prevent="submitManual" class="flex gap-2">
            <input v-model="manual" type="text" autocomplete="off" aria-label="手动输入条码、ISBN 或编号"
                placeholder="手动输入条码 / ISBN / 编号"
                class="flex-1 min-w-0 h-12 px-4 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500" />
            <button type="submit" :disabled="isBusy || !manual.trim()"
                class="h-12 px-6 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 disabled:opacity-50 active:scale-95 transition">
                查找
            </button>
        </form>

        <!-- What was done in this session -->
        <section v-if="activity.length" aria-labelledby="activity-title" class="space-y-2">
            <h2 id="activity-title" class="text-sm font-semibold text-gray-500">刚才的操作</h2>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700 rounded-2xl bg-gray-50 dark:bg-gray-900 ring-1 ring-gray-200/70 dark:ring-gray-700">
                <li v-for="a in activity" :key="a.id" class="flex items-start gap-3 p-3 text-sm">
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold" :class="icon[a.kind]">{{ label[a.kind] }}</span>
                    <span class="min-w-0 break-words">{{ a.text }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
