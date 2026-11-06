<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import type { MemberInfo } from '../types';

const lib = useLibraryStore();
const { members, isLoadingMembers } = storeToRefs(lib);
const query = ref(lib.memberSearch);
let timer: number | undefined;

function onInput(): void {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => lib.loadMembers(query.value), 300);
}

function lend(m: MemberInfo): void {
    lib.borrower = m;
    lib.setActiveTab('scan');
}

onBeforeUnmount(() => window.clearTimeout(timer));
</script>

<template>
    <div class="space-y-4">
        <input v-model="query" @input="onInput" type="search" autocomplete="off" aria-label="搜索会员"
            placeholder="搜索姓名或邮箱"
            class="w-full h-12 px-4 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500" />

        <ul class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <li v-for="m in members" :key="m.id"
                class="min-w-0 flex items-center gap-3 p-4 rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-gray-200/70 dark:ring-gray-700 shadow-sm">
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold truncate">{{ m.name }}</h3>
                    <p class="text-sm text-gray-500 truncate">{{ m.email || m.phone || '没有联系方式' }}</p>
                    <p v-if="m.activeLoans" class="text-xs text-primary-800 dark:text-primary-300 font-semibold">借了 {{ m.activeLoans }} 本</p>
                </div>
                <button type="button" @click="lend(m)"
                    class="shrink-0 h-10 px-4 rounded-xl text-sm font-semibold text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40 hover:bg-primary-100/60 active:scale-95 transition">
                    借书
                </button>
            </li>
        </ul>

        <p v-if="isLoadingMembers" class="py-4 text-center text-sm text-gray-500">加载中...</p>
        <p v-else-if="!members.length" class="py-10 text-center text-sm text-gray-500">没有找到会员。新借阅人可以在借书时添加。</p>
    </div>
</template>
