<template>
    <div>
        <ul class="flex flex-row gap-x-4">
            <template v-for="group in groupFilter">
                <li class="text-gray-700 dark:text-gray-400 font-semibold text-sm cursor-pointer" @click="onToggleGroupFilter(group)">
                    <div class="flex flex-row items-center gap-x-2">
                        <i v-show="group.enabled" :style="`color: ${group.color};`"
                            class="w-4 h-full text-gray-600 fill-current">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path
                                    d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" />
                            </svg>
                        </i>
                        <i v-show="!group.enabled" :style="`color: ${group.color};`"
                            class="w-4 h-full text-gray-600 fill-current">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path
                                    d="M0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96z" />
                            </svg>
                        </i>
                        <span :style="`color: ${group.color};`">{{ group.name }}</span>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</template>

<script setup lang="ts">

import { onMounted } from 'vue';
import { useTaskPlanStore } from '../stores';
import { storeToRefs } from 'pinia';

const taskPlanStore = useTaskPlanStore();
const { groupFilter } = storeToRefs(taskPlanStore);

onMounted(async () => {

});

const onToggleGroupFilter = (group: GroupFilterItem) => {
    group.enabled = !group.enabled;
};

</script>