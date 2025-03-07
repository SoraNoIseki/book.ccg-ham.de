<template>
    <div class="relative py-2 sm:px-4 sm:py-6">
        <h2 class="my-4 text-gray-700 dark:text-gray-400 font-semibold">
            服事安排
        </h2>

        <GroupFilterComponent class="my-4" />

        <div class="inline-flex rounded-md shadow-xs mb-4" role="group">
            <button type="button" @click="taskPlanStore.prevPlanMonth"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border border-gray-200 rounded-s-lg focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                上一月
            </button>
            <button type="button" @click="taskPlanStore.backToCurrentMonth"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border-t border-b border-r border-gray-200 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                回到 {{ taskPlanStore.now.toFormat('M') }} 月
            </button>
            <button type="button" @click="taskPlanStore.goToNextMonth"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border-t border-b border-gray-200 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                {{ taskPlanStore.now.plus({ months: 1 }).toFormat('M') }} 月
            </button>
            <button type="button" @click="taskPlanStore.nextPlanMonth"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border border-gray-200 rounded-e-lg focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                下一月
            </button>
        </div>

        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert" v-show="conflictMembers.length > 0 && !loadingPlan">
            <template v-for="conflict in conflictMembers">
                <p class="mb-2">
                    <span class="font-semibold">{{ conflict.name }}</span> 在 <span class="font-semibold">{{ conflict.date }}</span> 有多个服事：
                    <span v-for="(role, index) in conflict.roles" :key="index">
                        <span v-if="index > 0" class="mx-2">|</span>
                        <span class="font-semibold">{{ getGroupRoleName(role) }}</span>
                    </span>
                </p>
            </template>
        </div>

        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-fixed rounded-md">
            <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr class="bg-primary-200 border-b dark:border-gray-700 border-gray-200">
                    <th scope="col" class="w-36 h-12"></th>
                    <template v-for="date in sundays">
                        <th scope="col" class="p-2 w-1/{{ sundays.length }}">{{ date.toFormat('yyyy年M月d日') }}</th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template v-for="group in groups">
                    <template v-for="role in group.roles">
                        <tr v-show="isGroupVisible(group)" :style="`background-color: ${group.color}55;`"
                            class="border-b dark:border-gray-700 border-gray-200">
                            <td class="p-2 font-medium text-gray-900 whitespace-nowrap dark:text-white w-auto">
                                {{ role.name }}
                            </td>
                            <template v-for="(date, index) in sundays">
                                <td class="p-2 font-bold text-lg cursor-pointer relative w-1/{{ sundays.length }}">
                                    <div class="h-full w-full flex items-center justify-center" v-show="!loadingPlan">
                                        <UiMultiSelect v-if="role.type === 'select'"
                                            :placeholder="role.placeholder ?? '请选择'"
                                            v-model="planForm[role.role]['week' + (index + 1).toString()]"
                                            :options="selectItemsByRole(role.role)" :max="role.max"
                                            :disabled="!isUserHasPermission(group.permission)"
                                            @select="onChangeMembers($event, role.role, date, group.permission)"
                                            :border-color="group.color"
                                        >
                                        </UiMultiSelect>

                                        <div v-if="role.type === 'input'" class="flex items-center justify-center h-full w-full">
                                            <textarea rows="3" v-model="planForm[role.role]['week' + (index + 1).toString()]"
                                                :placeholder="role.placeholder ?? '请输入'"
                                                :disabled="!isUserHasPermission(group.permission)"
                                                @change="onInput($event, role.role, date)"
                                                class="block p-2.5 w-full min-w-[160px] text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                :style="`border-color: ${group.color}`"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- Dummy animation -->
                                    <div v-show="loadingPlan" class="animate-pulse bg-white rounded-md p-3 border w-auto" :style="`border-color: ${group.color}`">
                                        <div class="h-4 rounded w-full opacity-50" :style="`background-color: ${group.color}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
</template>


<script setup lang="ts">

import { ref, Ref, onMounted, computed } from 'vue';
import { useTaskPlanStore } from '../stores';
import { storeToRefs } from 'pinia';
import { DateTime } from 'luxon';
import { UiMultiSelect, GroupFilterComponent } from './';
import { TaskPlanService } from '../services';


const taskPlanStore = useTaskPlanStore();
const { groups, sortedMembersByRole, planForm, groupFilter, sundays, conflictMembers, groupRoles, loadingPlan } = storeToRefs(taskPlanStore);

onMounted(async () => {
    
});

const selectItemsByRole = computed(() => {
    return (role: string) => {
        return sortedMembersByRole.value.find((item: any) => item.role === role)?.names.map((name) => {
            return { value: name, label: name };
        }) || [];
    };
});

const onChangeMembers = (value: string[], role: string, date: DateTime, permission: string) => {
    if (!isUserHasPermission(permission)) {
        return;
    }

    taskPlanStore.updateTaskPlan(role, value.join('+'), date.toISODate() ?? '');
};

const onInput = (event: Event, role: string, date: DateTime) => {
    const target = event.target as HTMLTextAreaElement;
    taskPlanStore.updateTaskPlan(role, target.value.trim(), date.toISODate() ?? '');
};

const isGroupVisible = (group: Group) => {
    return groupFilter.value.find((filter: GroupFilterItem) => filter.name === group.group)?.enabled ?? true;
};

const isUserHasPermission = (permission: string) => {
    return TaskPlanService.isUserHasPermission(permission);
};

const getGroupRoleName = (role: string) => {
    return groupRoles.value.find((r: GroupRole) => r.role === role)?.name ?? '';
};

</script>