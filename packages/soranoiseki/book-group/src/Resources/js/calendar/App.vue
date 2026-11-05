<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from './store/calendar';
import StickyToolbar from '../shared/ui/StickyToolbar.vue';
import TabBar, { type TabItem } from '../shared/ui/TabBar.vue';
import MessageList from '../shared/ui/MessageList.vue';
import VersionBar from './components/VersionBar.vue';
import ActionBar from './components/ActionBar.vue';
import ImagesTab from './components/ImagesTab.vue';
import EventsTab from './components/EventsTab.vue';
import BibleTab from './components/BibleTab.vue';

const cal = useCalendarStore();
const { activeTab, hasCalendar, isLoading, year, errors, successMessage, events, requiredImagesDone, holidayCount } = storeToRefs(cal);

// Version comes from ?year= (the backend falls back to the default year).
onMounted(() => cal.fetch());

const tabs = computed<TabItem[]>(() => {
    const images = requiredImagesDone.value;
    const bible = cal.monthDetailsDone;
    return [
        {
            id: 'images', label: '图片',
            icon: 'M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM8.5 10a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM21 15l-5-5L5 21',
            status: images >= 13 ? 'done' : 'partial', badge: `${images}/13`,
        },
        {
            id: 'events', label: '大事历',
            icon: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z',
            status: events.value.length ? 'done' : undefined,
        },
        {
            id: 'bible', label: '每月经文',
            icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
            status: bible >= 12 ? 'done' : 'partial', badge: `${bible}/12`,
        },
    ];
});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <StickyToolbar v-slot="{ stuck }">
            <div class="flex flex-wrap justify-between gap-x-4 transition-all duration-300 ease-out"
                :class="stuck ? 'items-center gap-y-2' : 'items-end gap-y-4'">
                <VersionBar :compact="stuck" />
                <ActionBar :compact="stuck" />
            </div>
            <div v-if="hasCalendar" class="transition-all duration-300 ease-out" :class="stuck ? 'mt-2' : 'mt-4'">
                <TabBar :tabs="tabs" :model-value="activeTab" :compact="stuck"
                    @update:model-value="cal.setActiveTab($event as any)" />
            </div>
        </StickyToolbar>

        <div class="mb-6 space-y-2">
            <MessageList :errors="errors" :success="successMessage" />

            <div v-if="hasCalendar && holidayCount === 0" role="status"
                class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm text-amber-900 rounded-xl bg-amber-50 ring-1 ring-amber-200">
                <span>还没有 {{ year }} 年的节假日数据，日历上不会标出法定节假日。</span>
                <button type="button" :disabled="isLoading" @click="cal.refreshHolidays()"
                    class="h-8 px-4 rounded-lg text-xs font-semibold text-amber-900 bg-amber-100 hover:bg-amber-200 disabled:opacity-50 transition">
                    重新获取节假日
                </button>
            </div>
        </div>

        <!-- No calendar for this year yet -->
        <section v-if="!hasCalendar && !isLoading && year"
            class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 p-10 text-center text-main dark:text-gray-100">
            <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-100 text-primary-800">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4" />
                </svg>
            </div>
            <h2 class="text-lg font-bold">还没有 {{ year }} 年的日历</h2>
            <p class="mt-1 text-sm text-gray-500">创建后可以上传图片、大事历和每月经文，并生成 PDF。节假日会自动获取。</p>
            <button type="button" @click="cal.create()"
                class="mt-6 inline-flex items-center gap-2 h-11 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-600">
                创建 {{ year }} 年日历
            </button>
        </section>

        <section v-else-if="hasCalendar"
            class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 p-5 sm:p-8 text-main dark:text-gray-100 transition-opacity"
            :class="isLoading ? 'opacity-60 pointer-events-none' : ''" :aria-busy="isLoading">
            <ImagesTab v-show="activeTab === 'images'" />
            <EventsTab v-show="activeTab === 'events'" />
            <BibleTab v-show="activeTab === 'bible'" />
        </section>
    </div>
</template>
