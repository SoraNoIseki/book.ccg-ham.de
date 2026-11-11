<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from '../store/library';
import BottomSheet from '../../shared/ui/BottomSheet.vue';
import MessageList from '../../shared/ui/MessageList.vue';
import MemberPicker from './MemberPicker.vue';
import BookEditForm from './BookEditForm.vue';
import { dueText, fmtDate } from '../format';
import type { CopyInfo } from '../types';

// Shows a scanned (or tapped) book with its copies and the lend / return / renew buttons.
const lib = useLibraryStore();
const { result, borrower, isBusy, errors, successMessage, summary } = storeToRefs(lib);

const book = computed(() => result.value?.book ?? null);
const hasFreeCopy = computed(() => !!book.value?.copies.some((c) => c.status === 'active' && !c.loan));

// Managers can switch the sheet from the lending view to an edit form and back.
const editing = ref(false);
watch(() => result.value?.book?.id, () => { editing.value = false; });

function close(): void {
    editing.value = false;
    lib.closeResult();
}

function startEdit(): void {
    lib.clearMessages();
    editing.value = true;
}

function endEdit(): void {
    editing.value = false;
}

function scanNext(): void {
    lib.closeResult();
    lib.clearMessages();
    lib.openScanner();
}

function state(c: CopyInfo): 'free' | 'out' | 'lost' | 'retired' {
    if (c.status === 'lost') return 'lost';
    if (c.status === 'retired') return 'retired';
    return c.loan ? 'out' : 'free';
}

const btn = 'inline-flex items-center justify-center h-11 px-5 rounded-xl text-sm font-semibold active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-primary-600';
</script>

<template>
    <BottomSheet :open="!!result" :title="book ? (editing ? '编辑图书' : '图书') : '没有找到'" @close="close">
        <div class="space-y-4 pt-1">
            <MessageList :errors="errors" :success="successMessage" />

            <!-- Nothing matched -->
            <template v-if="result && !book">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    图书馆里没有找到
                    <span class="font-mono font-semibold break-all">{{ result.code }}</span>。
                </p>
                <p v-if="!lib.canManage" class="text-sm text-gray-500">如果这是一本新书，请让图书管理员先添加。</p>
                <div class="flex gap-2">
                    <button v-if="lib.canManage" type="button" :class="btn + ' flex-1 text-white bg-gradient-to-r from-primary-500 to-primary-700'"
                        @click="lib.startAddBook(result.isbn ?? '')">添加这本书</button>
                    <button type="button" :class="btn + ' flex-1 text-primary-800 bg-white ring-1 ring-primary-600/40 dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700'" @click="scanNext">重新扫码</button>
                </div>
            </template>

            <!-- Edit the details (managers) -->
            <BookEditForm v-else-if="book && editing" :key="book.id" :book="book" @done="endEdit" />

            <template v-else-if="book">
                <!-- Book -->
                <div class="flex gap-4">
                    <img v-if="book.coverUrl" :src="book.coverUrl" alt="" class="w-16 h-24 object-cover rounded-lg shadow ring-1 ring-gray-200 shrink-0" />
                    <div class="min-w-0">
                        <h3 class="font-bold text-lg leading-snug break-words">{{ book.title }}</h3>
                        <p v-if="book.subtitle" class="text-sm text-gray-500 break-words">{{ book.subtitle }}</p>
                        <p v-if="book.author" class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ book.author }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            <span class="font-mono">{{ book.code }}</span>
                            <span v-if="book.publisher"> · {{ book.publisher }}</span>
                            <span v-if="book.category"> · {{ book.category }}</span>
                        </p>
                        <p v-if="book.isbn" class="text-xs text-gray-400 font-mono">ISBN {{ book.isbn }}</p>
                        <button v-if="lib.canManage" type="button" @click="startEdit"
                            class="mt-2 inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-semibold text-primary-800 dark:text-primary-300 ring-1 ring-primary-600/40 hover:bg-primary-100/60 active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                            编辑
                        </button>
                    </div>
                </div>

                <MemberPicker v-if="hasFreeCopy" />

                <!-- Copies -->
                <ul class="space-y-2">
                    <li v-for="c in book.copies" :key="c.id"
                        class="rounded-2xl p-3 ring-1 transition"
                        :class="[
                            result?.copyId === c.id ? 'ring-2 ring-primary-500 bg-primary-100/40' : 'ring-gray-200/70 dark:ring-gray-700 bg-white dark:bg-gray-900',
                        ]">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" aria-hidden="true"
                                :class="{ 'bg-green-500': state(c) === 'free', 'bg-red-500': state(c) === 'out', 'bg-gray-400': state(c) === 'lost' || state(c) === 'retired' }"></span>
                            <div class="flex-1 min-w-0">
                                <div class="font-mono text-sm font-semibold">{{ c.code }}</div>
                                <div class="text-xs text-gray-500 truncate">
                                    <template v-if="state(c) === 'free'">在馆，可以借出</template>
                                    <template v-else-if="state(c) === 'out' && c.loan">
                                        {{ c.loan.member.name }} 借走 ·
                                        <span :class="c.loan.overdue ? 'text-red-600 font-semibold' : ''">{{ fmtDate(c.loan.dueAt) }} 到期（{{ dueText(c.loan.dueAt) }}）</span>
                                    </template>
                                    <template v-else-if="state(c) === 'lost'">已遗失</template>
                                    <template v-else>已下架</template>
                                </div>
                            </div>
                        </div>

                        <div v-if="state(c) === 'free'" class="mt-3">
                            <button type="button" :disabled="isBusy || !borrower" @click="lib.borrow(c.id)"
                                :class="btn + ' w-full text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25'">
                                {{ borrower ? `借给 ${borrower.name}` : '请先选择借阅人' }}
                            </button>
                        </div>
                        <div v-else-if="state(c) === 'out' && c.loan" class="mt-3 flex gap-2">
                            <button type="button" :disabled="isBusy" @click="lib.returnCopy(c.id)"
                                :class="btn + ' flex-1 text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25'">
                                归还
                            </button>
                            <button type="button" :disabled="isBusy" @click="lib.renew(c.loan.id)"
                                :class="btn + ' text-primary-800 dark:text-primary-300 bg-white dark:bg-gray-800 ring-1 ring-primary-600/40'">
                                续借 {{ summary?.loanDays ?? 28 }} 天
                            </button>
                        </div>
                    </li>
                </ul>

                <button type="button" @click="scanNext"
                    :class="btn + ' w-full text-primary-800 dark:text-primary-300 bg-white dark:bg-gray-800 ring-1 ring-primary-600/40'">
                    <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10" />
                    </svg>
                    继续扫码
                </button>
            </template>
        </div>
    </BottomSheet>
</template>
