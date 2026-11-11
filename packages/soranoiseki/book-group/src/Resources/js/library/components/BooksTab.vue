<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import BookRow from './BookRow.vue';

const lib = useLibraryStore();
const { books, search, filter, isLoadingBooks, booksPage, booksLastPage, booksTotal, summary } = storeToRefs(lib);

const fileInput = ref<HTMLInputElement | null>(null);
let timer: number | undefined;

function onSearch(): void {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => lib.loadBooks(true), 300);
}

function setFilter(f: 'all' | 'available' | 'borrowed'): void {
    filter.value = f;
    lib.loadBooks(true);
}

function onFile(e: Event): void {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) lib.importBooks(file);
    input.value = '';
}

onBeforeUnmount(() => window.clearTimeout(timer));

const filters = [
    { id: 'all', label: '全部' },
    { id: 'available', label: '可借' },
    { id: 'borrowed', label: '已借出' },
] as const;
</script>

<template>
    <div class="space-y-4">
        <div class="flex gap-2">
            <input v-model="search" @input="onSearch" type="search" autocomplete="off" aria-label="搜索书名、作者、编号或 ISBN"
                placeholder="搜索图书"
                class="flex-1 min-w-0 h-12 px-4 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500" />
            <!-- Scanning is the main way to add books: filled and prominent. Typing it in by hand is the fallback: quiet. -->
            <button v-if="lib.canManage" type="button" @click="lib.openScanner('add')" aria-label="扫码添加图书"
                class="shrink-0 inline-flex items-center justify-center gap-1.5 h-12 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/30 hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 8v8M11 8v8M15 8v8M18 8v8" />
                </svg>
                扫码
            </button>
            <button v-if="lib.canManage" type="button" @click="lib.startAddBook()"
                class="shrink-0 h-12 px-3 rounded-xl text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-primary-800 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                手动添加
            </button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex gap-1 p-1 rounded-xl bg-gray-100 dark:bg-gray-900" role="group" aria-label="筛选">
                <button v-for="f in filters" :key="f.id" type="button" @click="setFilter(f.id)" :aria-pressed="filter === f.id"
                    class="px-4 h-9 rounded-lg text-sm font-semibold transition outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    :class="filter === f.id ? 'bg-white text-primary-800 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-main'">
                    {{ f.label }}
                </button>
            </div>
            <span class="text-sm text-gray-500 tabular-nums">共 {{ booksTotal }} 本{{ summary ? `（${summary.copies} 册）` : '' }}</span>
        </div>

        <ul class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <li v-for="b in books" :key="b.id" class="min-w-0"><BookRow :book="b" @open="lib.openBook" /></li>
        </ul>

        <p v-if="!books.length && !isLoadingBooks" class="py-10 text-center text-sm text-gray-500">没有找到符合条件的图书。</p>
        <p v-if="isLoadingBooks" class="py-4 text-center text-sm text-gray-500">加载中...</p>

        <button v-if="booksPage < booksLastPage && !isLoadingBooks" type="button" @click="lib.loadBooks()"
            class="w-full h-12 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 active:scale-95 transition">
            加载更多
        </button>

        <div v-if="lib.canManage" class="pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500">
            <p class="mb-2">批量导入：CSV 或 Excel，每行一本书：编号;书名;作者;出版社;分类;册数</p>
            <input ref="fileInput" type="file" accept=".csv,.xlsx,.xls,.txt" class="hidden" @change="onFile" />
            <button type="button" @click="fileInput?.click()" :disabled="lib.isBusy"
                class="h-10 px-4 rounded-lg text-sm font-semibold text-primary-800 ring-1 ring-primary-600/40 dark:text-primary-200 dark:ring-primary-400/50 dark:hover:bg-gray-700 hover:bg-primary-100/60 disabled:opacity-50 transition">
                选择文件导入
            </button>
        </div>
    </div>
</template>
