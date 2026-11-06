<script setup lang="ts">
import { computed } from 'vue';
import type { BookInfo } from '../types';

const props = defineProps<{ book: BookInfo }>();
defineEmits<{ (e: 'open', book: BookInfo): void }>();

const total = computed(() => props.book.copies.length);
const free = computed(() => props.book.copies.filter((c) => c.status === 'active' && !c.loan).length);
</script>

<template>
    <button type="button" @click="$emit('open', book)"
        class="w-full flex items-center gap-3 p-4 text-left rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-gray-200/70 dark:ring-gray-700 shadow-sm hover:shadow-md active:scale-[0.99] transition outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold leading-snug text-main dark:text-gray-100 break-words">{{ book.title }}</h3>
            <p class="mt-0.5 text-sm text-gray-500 truncate">
                <span v-if="book.author">{{ book.author }} · </span><span class="font-mono">{{ book.code }}</span>
            </p>
            <div class="mt-2 flex flex-wrap gap-1" aria-hidden="true">
                <span v-for="c in book.copies" :key="c.id" class="w-3 h-3 rounded-full"
                    :class="c.status !== 'active' ? 'bg-gray-300' : c.loan ? 'bg-red-500' : 'bg-green-500'"></span>
            </div>
        </div>
        <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold tabular-nums"
            :class="free ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
            {{ free ? `在馆 ${free}/${total}` : '已借完' }}
        </span>
    </button>
</template>
