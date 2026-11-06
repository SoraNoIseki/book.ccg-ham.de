<script setup lang="ts">
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import type { BookEdit, BookInfo } from '../types';

// Edit the details of an existing book (managers). Changing the catalogue number renames the
// copies too (CODE.1, CODE.2 ...), so labels that are already stuck on the books no longer match.
const props = defineProps<{ book: BookInfo }>();
const emit = defineEmits<{ (e: 'done'): void }>();

const lib = useLibraryStore();
const { isBusy } = storeToRefs(lib);

const form = ref<BookEdit>({
    code: props.book.code,
    title: props.book.title,
    subtitle: props.book.subtitle ?? '',
    isbn: props.book.isbn ?? '',
    author: props.book.author ?? '',
    publisher: props.book.publisher ?? '',
    category: props.book.category ?? '',
});

const codeChanged = computed(() => form.value.code.trim() !== props.book.code);

async function save(): Promise<void> {
    if (await lib.updateBook(props.book, form.value)) emit('done');
}

const field = 'w-full h-11 mt-1 px-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500';
</script>

<template>
    <form @submit.prevent="save" class="space-y-3">
        <label class="block text-xs text-gray-500">书名 *
            <input v-model="form.title" type="text" required :class="field" />
        </label>
        <label class="block text-xs text-gray-500">副标题
            <input v-model="form.subtitle" type="text" :class="field" />
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="block text-xs text-gray-500">作者
                <input v-model="form.author" type="text" :class="field" class="min-w-0" />
            </label>
            <label class="block text-xs text-gray-500">出版社
                <input v-model="form.publisher" type="text" :class="field" class="min-w-0" />
            </label>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <label class="block text-xs text-gray-500">分类
                <input v-model="form.category" type="text" :class="field" class="min-w-0" />
            </label>
            <label class="block text-xs text-gray-500">编号 *
                <input v-model="form.code" type="text" required autocomplete="off" :class="field" class="min-w-0 font-mono" />
            </label>
        </div>
        <div v-if="codeChanged" class="p-3 text-sm rounded-xl bg-amber-50 text-amber-900 ring-1 ring-amber-200">
            册的编号会一起改成
            <span v-for="(c, i) in book.copies" :key="c.id" class="font-mono">{{ form.code.trim() }}.{{ c.number }}{{ i < book.copies.length - 1 ? '、' : '' }}</span>。
            已经贴在书上的旧标签需要重新打印。
        </div>
        <label class="block text-xs text-gray-500">ISBN
            <input v-model="form.isbn" type="text" inputmode="numeric" autocomplete="off" placeholder="10 或 13 位，可留空" :class="field" />
        </label>

        <div class="flex gap-2 pt-2">
            <button type="button" @click="emit('done')"
                class="h-12 px-5 rounded-xl text-sm font-semibold text-gray-600 ring-1 ring-gray-300 active:scale-95 transition">取消</button>
            <button type="submit" :disabled="isBusy || !form.title.trim() || !form.code.trim()"
                class="flex-1 h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                {{ isBusy ? '保存中...' : '保存' }}
            </button>
        </div>
    </form>
</template>
