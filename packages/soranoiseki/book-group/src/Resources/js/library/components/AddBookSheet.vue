<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import BottomSheet from '../../shared/ui/BottomSheet.vue';
import MessageList from '../../shared/ui/MessageList.vue';

// Add a book (managers). With an ISBN the details come from Google Books. If the ISBN is already
// in the library, the manager is asked whether to add one more copy instead of a duplicate book.
const lib = useLibraryStore();
const { addBookDraft: draft, addBookExisting: existing, addBookDone: done, isBusy, isLookingUpIsbn, errors } = storeToRefs(lib);

const open = computed(() => !!draft.value || !!done.value);
/** Number the new copy would get */
const nextNumber = computed(() => (existing.value ? Math.max(0, ...existing.value.copies.map((c) => c.number)) + 1 : 1));

function close(): void {
    lib.closeAddBook();
}

/** Done with this book: go on with the next one */
function scanNext(): void {
    lib.closeAddBook();
    lib.openScanner('add');
}

function lookup(): void {
    if (draft.value?.isbn.trim()) lib.fillFromIsbn(draft.value);
}

const field = 'w-full h-11 px-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500';
</script>

<template>
    <BottomSheet :open="open" :title="done ? '已保存' : existing ? '图书已在馆里' : '添加图书'" @close="close">
        <!-- Saved: only the catalogue matters here, so no lending buttons. Go on or finish. -->
        <div v-if="done" class="space-y-4 pt-1">
            <MessageList :success="done.message" />

            <div class="flex gap-4">
                <img v-if="done.book.coverUrl" :src="done.book.coverUrl" alt="" class="w-16 h-24 object-cover rounded-lg shadow ring-1 ring-gray-200 shrink-0" />
                <div class="min-w-0">
                    <h3 class="font-bold text-lg leading-snug break-words">{{ done.book.title }}</h3>
                    <p v-if="done.book.author" class="mt-1 text-sm text-gray-600 dark:text-gray-300 break-words">{{ done.book.author }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        <span class="font-mono">{{ done.book.code }}</span>
                        <span v-if="done.book.isbn"> · ISBN <span class="font-mono">{{ done.book.isbn }}</span></span>
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        册：<span v-for="(c, i) in done.book.copies" :key="c.id" class="font-mono">{{ c.code }}{{ i < done.book.copies.length - 1 ? '、' : '' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" @click="close"
                    class="h-12 px-6 rounded-xl text-sm font-semibold text-gray-600 ring-1 ring-gray-300 active:scale-95 transition">完成</button>
                <button type="button" @click="scanNext"
                    class="flex-1 inline-flex items-center justify-center gap-2 h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 active:scale-95 transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10" />
                    </svg>
                    继续扫码添加
                </button>
            </div>
        </div>

        <!-- The ISBN is already in the library: ask before adding another copy -->
        <div v-else-if="existing" class="space-y-4 pt-1">
            <MessageList :errors="errors" />

            <div class="flex gap-4">
                <img v-if="existing.coverUrl" :src="existing.coverUrl" alt="" class="w-16 h-24 object-cover rounded-lg shadow ring-1 ring-gray-200 shrink-0" />
                <div class="min-w-0">
                    <h3 class="font-bold text-lg leading-snug break-words">{{ existing.title }}</h3>
                    <p v-if="existing.author" class="mt-1 text-sm text-gray-600 dark:text-gray-300 break-words">{{ existing.author }}</p>
                    <p class="mt-1 text-xs text-gray-500"><span class="font-mono">{{ existing.code }}</span> · ISBN <span class="font-mono">{{ existing.isbn }}</span></p>
                </div>
            </div>

            <div class="p-3 text-sm rounded-xl bg-amber-50 text-amber-900 ring-1 ring-amber-200">
                图书馆已经有这本书，现有 {{ existing.copies.length }} 册。要把它添加为新的一册（第 {{ nextNumber }} 册）吗？
            </div>

            <div class="flex gap-2">
                <button type="button" @click="close"
                    class="h-12 px-5 rounded-xl text-sm font-semibold text-gray-600 ring-1 ring-gray-300 active:scale-95 transition">不添加</button>
                <button type="button" :disabled="isBusy" @click="lib.addCopy(existing)"
                    class="flex-1 h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                    {{ isBusy ? '添加中...' : `添加为第 ${nextNumber} 册` }}
                </button>
            </div>
        </div>

        <form v-else-if="draft" @submit.prevent="lib.saveBook(draft)" class="space-y-3 pt-1">
            <MessageList :errors="errors" />

            <label class="block text-xs text-gray-500">ISBN
                <div class="flex gap-2 mt-1">
                    <input v-model="draft.isbn" type="text" inputmode="numeric" autocomplete="off" placeholder="10 或 13 位，可留空" :class="field" class="min-w-0 flex-1" />
                    <button type="button" @click="lib.openScanner('isbn')" aria-label="扫描 ISBN 条形码"
                        class="shrink-0 w-11 h-11 flex items-center justify-center rounded-xl text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 8v8M11 8v8M15 8v8M18 8v8" />
                        </svg>
                    </button>
                    <button type="button" @click="lookup" :disabled="isLookingUpIsbn || !draft.isbn.trim()"
                        class="shrink-0 h-11 px-4 rounded-xl text-sm font-semibold text-primary-800 ring-1 ring-primary-600/40 hover:bg-primary-100/60 disabled:opacity-50 active:scale-95 transition">
                        {{ isLookingUpIsbn ? '查询中...' : '查询资料' }}
                    </button>
                </div>
            </label>

            <label class="block text-xs text-gray-500">书名 *
                <input v-model="draft.title" type="text" required :class="field" class="mt-1" />
            </label>
            <label class="block text-xs text-gray-500">副标题
                <input v-model="draft.subtitle" type="text" :class="field" class="mt-1" />
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs text-gray-500">作者
                    <input v-model="draft.author" type="text" :class="field" class="mt-1" />
                </label>
                <label class="block text-xs text-gray-500">出版社
                    <input v-model="draft.publisher" type="text" :class="field" class="mt-1" />
                </label>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs text-gray-500">分类
                    <input v-model="draft.category" type="text" :class="field" class="mt-1" />
                </label>
                <label class="block text-xs text-gray-500">编号
                    <input v-model="draft.code" type="text" :readonly="!!existing" placeholder="留空自动生成" :class="field" class="mt-1" />
                </label>
            </div>
            <label class="block text-xs text-gray-500">册数（这本书一共有几本）
                <input v-model.number="draft.quantity" type="number" min="1" max="50" required inputmode="numeric" :class="field" class="mt-1" />
            </label>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="close"
                    class="h-12 px-5 rounded-xl text-sm font-semibold text-gray-600 ring-1 ring-gray-300 active:scale-95 transition">取消</button>
                <button type="submit" :disabled="isBusy || !draft.title.trim()"
                    class="flex-1 h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                    {{ isBusy ? '保存中...' : '保存' }}
                </button>
            </div>
        </form>
    </BottomSheet>
</template>
