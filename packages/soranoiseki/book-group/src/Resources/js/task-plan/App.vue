<template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6 overflow-x-hidden">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-y-visible mb-8" id="add-member-component" v-if="isUserHasEditPermission">
            <div class="text-main dark:text-gray-100">
                <div class="px-3 py-2 md:px-4 md:py-4">
                    <div class="relative">
                        <div class="">
                            <AddMemberComponent />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-y-visible mb-8" id="planer-component">
            <div class="text-main dark:text-gray-100">
                <div class="px-3 py-2 md:px-4 md:py-4">
                    <div class="relative">
                        <div class="mb-8">
                            <PlanerComponent />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-y-visible mb-8" id="members-component">
            <div class="text-main dark:text-gray-100">
                <div class="px-3 py-2 md:px-4 md:py-4">
                    <div class="relative">
                        <div class="mb-4">
                            <MembersComponent />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">

import { ref, Ref, onMounted, watch } from 'vue';
import { useTaskPlanStore } from './stores';
import { MembersComponent, PlanerComponent, AddMemberComponent } from './components';
import { storeToRefs } from 'pinia';

const taskPlanStore = useTaskPlanStore();
const { isUserHasEditPermission } = storeToRefs(taskPlanStore);

onMounted(async () => {
    taskPlanStore.init();
});

watch(() => taskPlanStore.groupFilter, () => {
    // save to local storage
    localStorage.setItem('groupFilter', JSON.stringify(taskPlanStore.groupFilter));
}, { deep: true });


</script>
