<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import type { MemberInfo, NewMember } from '../types';

// Chooses who borrows. The pick stays in the store, so after one book the next scan can be lent
// to the same person with a single tap.
const lib = useLibraryStore();
const { borrower, isBusy } = storeToRefs(lib);

const open = ref(false);
const query = ref('');
const results = ref<MemberInfo[]>([]);
const creating = ref(false);
const form = ref<NewMember>({ first_name: '', last_name: '', email: '', phone: '' });
let timer: number | undefined;

async function search(): Promise<void> {
    results.value = await lib.loadMembers(query.value);
}

function onInput(): void {
    window.clearTimeout(timer);
    timer = window.setTimeout(search, 250);
}

function toggle(): void {
    open.value = !open.value;
    creating.value = false;
    if (open.value) search();
}

function pick(m: MemberInfo): void {
    borrower.value = m;
    open.value = false;
}

async function create(): Promise<void> {
    if (await lib.createMember(form.value)) {
        form.value = { first_name: '', last_name: '', email: '', phone: '' };
        open.value = false;
        creating.value = false;
    }
}

onBeforeUnmount(() => window.clearTimeout(timer));

const field = 'w-full h-11 px-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500';
</script>

<template>
    <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 ring-1 ring-gray-200/70 dark:ring-gray-700">
        <button type="button" @click="toggle" :aria-expanded="open"
            class="w-full flex items-center gap-3 p-3 text-left rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
            <span class="flex items-center justify-center w-10 h-10 rounded-full shrink-0"
                :class="borrower ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-200' : 'bg-gray-200 text-gray-500'">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                </svg>
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-xs text-gray-500">借阅人</span>
                <span class="block font-semibold truncate" :class="borrower ? '' : 'text-gray-400'">
                    {{ borrower ? borrower.name : '点这里选择借阅人' }}
                </span>
            </span>
            <span class="text-sm font-semibold text-primary-800 dark:text-primary-300">{{ open ? '收起' : borrower ? '更换' : '选择' }}</span>
        </button>

        <div v-if="open" class="px-3 pb-3 space-y-3">
            <template v-if="!creating">
                <input v-model="query" @input="onInput" type="search" placeholder="搜索姓名或邮箱" :class="field" autocomplete="off" />
                <ul class="max-h-56 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 rounded-xl bg-white dark:bg-gray-800 ring-1 ring-gray-200/70 dark:ring-gray-700">
                    <li v-for="m in results" :key="m.id">
                        <button type="button" @click="pick(m)"
                            class="w-full flex items-center justify-between gap-3 px-4 min-h-12 text-left hover:bg-primary-100/50 active:bg-primary-100 outline-none focus-visible:bg-primary-100">
                            <span class="font-medium truncate">{{ m.name }}</span>
                            <span v-if="m.activeLoans" class="shrink-0 text-xs text-gray-500">借了 {{ m.activeLoans }} 本</span>
                        </button>
                    </li>
                    <li v-if="!results.length" class="px-4 py-3 text-sm text-gray-500">没有找到，可以新建借阅人。</li>
                </ul>
                <button type="button" @click="creating = true"
                    class="w-full h-11 rounded-xl text-sm font-semibold text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40 hover:bg-primary-100/60 active:scale-95 transition">
                    + 新建借阅人
                </button>
            </template>

            <form v-else @submit.prevent="create" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <label class="block text-xs text-gray-500">姓
                        <input v-model="form.last_name" type="text" autocomplete="off" :class="field" />
                    </label>
                    <label class="block text-xs text-gray-500">名 *
                        <input v-model="form.first_name" type="text" required autocomplete="off" :class="field" />
                    </label>
                </div>
                <label class="block text-xs text-gray-500">邮箱
                    <input v-model="form.email" type="email" inputmode="email" autocomplete="off" :class="field" />
                </label>
                <label class="block text-xs text-gray-500">电话
                    <input v-model="form.phone" type="tel" inputmode="tel" autocomplete="off" :class="field" />
                </label>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="creating = false"
                        class="h-11 px-5 rounded-xl text-sm font-semibold text-gray-600 ring-1 ring-gray-300 active:scale-95 transition">返回</button>
                    <button type="submit" :disabled="isBusy || !form.first_name.trim()"
                        class="flex-1 h-11 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 disabled:opacity-50 active:scale-95 transition">
                        保存并选择
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
