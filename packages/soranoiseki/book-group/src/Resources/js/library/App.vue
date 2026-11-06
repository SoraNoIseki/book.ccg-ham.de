<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useLibraryStore } from './store/library';
import TabBar, { type TabItem } from '../shared/ui/TabBar.vue';
import MessageList from '../shared/ui/MessageList.vue';
import ScanTab from './components/ScanTab.vue';
import BooksTab from './components/BooksTab.vue';
import LoansTab from './components/LoansTab.vue';
import MembersTab from './components/MembersTab.vue';
import ResultSheet from './components/ResultSheet.vue';
import AddBookSheet from './components/AddBookSheet.vue';
import ScannerOverlay from './components/ScannerOverlay.vue';

const lib = useLibraryStore();
const { activeTab, summary, scannerOpen, scanPurpose, errors, successMessage, result, addBookDraft, addBookDone } = storeToRefs(lib);

onMounted(() => lib.init());

// Page-level messages are only for things outside the sheets; drop them when switching tabs.
watch(activeTab, () => lib.clearMessages());

function onScan(code: string): void {
    scannerOpen.value = false;

    // Scanning from the add-book form only fills its ISBN field and looks up the book data.
    if (scanPurpose.value === 'isbn' && addBookDraft.value) {
        addBookDraft.value.isbn = code.trim();
        lib.fillFromIsbn(addBookDraft.value);
        return;
    }
    if (scanPurpose.value === 'add') {
        lib.startAddFromScan(code);
        return;
    }
    lib.lookup(code);
}

// On the books tab a manager is managing the catalogue, so the scan button adds books there.
const fabPurpose = computed(() => (activeTab.value === 'books' && lib.canManage ? 'add' : 'lookup'));
const scanHint = computed(() => ({
    isbn: '扫描书背面的 ISBN 条形码',
    add: '扫描要添加的书的 ISBN 条形码',
    lookup: undefined,
}[scanPurpose.value]));

const tabs = computed<TabItem[]>(() => [
    {
        id: 'scan', label: '借还',
        icon: 'M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10',
    },
    {
        id: 'books', label: '图书',
        icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
    },
    {
        id: 'loans', label: '借阅中',
        icon: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
        status: summary.value?.overdue ? 'partial' : undefined, badge: `${summary.value?.overdue ?? 0}`,
    },
    {
        id: 'members', label: '会员',
        icon: 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM21 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    },
]);

const stats = computed(() => [
    { label: '图书', value: summary.value?.books ?? '-', tone: 'text-main dark:text-gray-100' },
    { label: '借出中', value: summary.value?.loans ?? '-', tone: 'text-primary-800 dark:text-primary-300' },
    { label: '已逾期', value: summary.value?.overdue ?? '-', tone: summary.value?.overdue ? 'text-red-600' : 'text-main dark:text-gray-100' },
]);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-28">
        <!-- Overview + tabs (not sticky: on a phone the screen is needed for the content) -->
        <div class="mb-6 rounded-2xl bg-white/90 dark:bg-gray-800/90 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 p-4 sm:p-5 space-y-4">
            <dl class="grid grid-cols-3 gap-3 text-center">
                <div v-for="s in stats" :key="s.label" class="rounded-xl bg-gray-50 dark:bg-gray-900 py-3">
                    <dd class="text-2xl font-bold tabular-nums" :class="s.tone">{{ s.value }}</dd>
                    <dt class="text-xs text-gray-500">{{ s.label }}</dt>
                </div>
            </dl>
            <TabBar :tabs="tabs" :model-value="activeTab" @update:model-value="lib.setActiveTab($event as any)" />
        </div>

        <!-- Messages that are not covered by a sheet -->
        <div v-if="!result && !addBookDraft && !addBookDone" class="mb-6">
            <MessageList :errors="errors" :success="successMessage" />
        </div>

        <section class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 p-4 sm:p-8 text-main dark:text-gray-100">
            <ScanTab v-show="activeTab === 'scan'" />
            <BooksTab v-show="activeTab === 'books'" />
            <LoansTab v-show="activeTab === 'loans'" />
            <MembersTab v-show="activeTab === 'members'" />
        </section>

        <!-- Scan button within reach of the thumb on every tab but the first -->
        <button v-if="activeTab !== 'scan'" type="button" @click="lib.openScanner(fabPurpose)" :aria-label="fabPurpose === 'add' ? '扫码添加图书' : '扫描条形码'"
            class="fixed right-4 bottom-[max(1rem,env(safe-area-inset-bottom))] z-40 w-16 h-16 flex items-center justify-center rounded-full text-white bg-gradient-to-br from-primary-500 to-primary-700 shadow-xl shadow-primary-700/40 active:scale-90 transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 8v8M11 8v8M15 8v8M18 8v8" />
            </svg>
        </button>

        <ScannerOverlay v-if="scannerOpen" :hint="scanHint"
            @scan="onScan" @close="scannerOpen = false" />
        <ResultSheet />
        <AddBookSheet />
    </div>
</template>
