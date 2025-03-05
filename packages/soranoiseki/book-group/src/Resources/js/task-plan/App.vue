<template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="text-main dark:text-gray-100">
                <div class="px-3 py-2 md:px-4 md:py-4">
                    <div class="relative overflow-x-auto">
                        <div class="mb-8">
                            <PlanerComponent />
                        </div>
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

import { ref, Ref, onMounted } from 'vue';
import { LoadingIcon, ChevronDownIcon } from '../icons';
import { useTaskPlanStore } from './stores';
import { MembersComponent, PlanerComponent } from './components';

const taskPlanStore = useTaskPlanStore();

onMounted(async () => {
    taskPlanStore.init();
});


</script>
