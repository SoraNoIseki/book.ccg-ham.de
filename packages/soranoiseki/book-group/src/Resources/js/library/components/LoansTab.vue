<script setup lang="ts">
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import { dueText, fmtDate } from '../format';

const lib = useLibraryStore();
const { loans, isLoadingLoans, isBusy } = storeToRefs(lib);
const filter = ref('');

const shown = computed(() => {
    const q = filter.value.trim().toLowerCase();
    if (!q) return loans.value;
    return loans.value.filter((l) =>
        [l.member.name, l.book.title, l.copyCode].some((v) => (v ?? '').toLowerCase().includes(q)));
});

const btn = 'inline-flex items-center justify-center h-10 px-4 rounded-xl text-sm font-semibold active:scale-95 transition disabled:opacity-50 outline-none focus-visible:ring-2 focus-visible:ring-primary-600';
</script>

<template>
    <div class="space-y-4">
        <input v-model="filter" type="search" autocomplete="off" aria-label="筛选借阅"
            placeholder="按借阅人或书名筛选"
            class="w-full h-12 px-4 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500" />

        <ul class="space-y-3">
            <li v-for="l in shown" :key="l.id"
                class="p-4 rounded-2xl bg-white dark:bg-gray-800 ring-1 shadow-sm"
                :class="l.overdue ? 'ring-red-300' : 'ring-gray-200/70 dark:ring-gray-700'">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold leading-snug break-words">{{ l.book.title }}</h3>
                        <p class="mt-0.5 text-sm text-gray-500">{{ l.member.name }} · <span class="font-mono">{{ l.copyCode }}</span></p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                        :class="l.overdue ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700'">
                        {{ dueText(l.dueAt) }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ fmtDate(l.borrowedAt) }} 借出 · {{ fmtDate(l.dueAt) }} 到期<span v-if="l.renewals"> · 已续借 {{ l.renewals }} 次</span></p>
                <div class="mt-3 flex gap-2">
                    <button type="button" :disabled="isBusy" @click="lib.returnCopy(l.copyId)"
                        :class="btn + ' flex-1 text-white bg-gradient-to-r from-primary-500 to-primary-700'">归还</button>
                    <button type="button" :disabled="isBusy" @click="lib.renew(l.id)"
                        :class="btn + ' text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40'">续借</button>
                </div>
            </li>
        </ul>

        <p v-if="isLoadingLoans" class="py-4 text-center text-sm text-gray-500">加载中...</p>
        <p v-else-if="!shown.length" class="py-10 text-center text-sm text-gray-500">
            {{ loans.length ? '没有符合的借阅。' : '现在没有借出的书。' }}
        </p>
    </div>
</template>
