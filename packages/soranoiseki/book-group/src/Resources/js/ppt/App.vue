<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from './store/powerpoint';
import StickyToolbar from '../shared/ui/StickyToolbar.vue';
import VersionSelector from './components/VersionSelector.vue';
import ActionBar from './components/ActionBar.vue';
import MessageBar from './components/MessageBar.vue';
import TabNav from './components/TabNav.vue';
import Form from './components/Form.vue';

const ppt = usePowerpointStore();
const { isLoading } = storeToRefs(ppt);

// Initial load: version comes from ?v= (falls back to this week on the backend).
onMounted(() => ppt.fetchInit());
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <StickyToolbar v-slot="{ stuck }">
            <div class="flex flex-wrap justify-between gap-x-4 transition-all duration-300 ease-out"
                :class="stuck ? 'items-center gap-y-2' : 'items-end gap-y-4'">
                <VersionSelector :compact="stuck" />
                <ActionBar :compact="stuck" />
            </div>
            <div class="transition-all duration-300 ease-out" :class="stuck ? 'mt-2' : 'mt-4'">
                <TabNav :compact="stuck" />
            </div>
        </StickyToolbar>

        <div class="mb-6">
            <MessageBar />
        </div>

        <section class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700 p-5 sm:p-8 text-main dark:text-gray-100 transition-opacity"
            :class="isLoading ? 'opacity-60 pointer-events-none' : ''" :aria-busy="isLoading">
            <Form />
        </section>
    </div>
</template>
