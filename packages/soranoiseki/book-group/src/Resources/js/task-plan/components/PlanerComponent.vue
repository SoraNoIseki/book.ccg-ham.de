<template>
    <div class="relative py-2 sm:px-4 sm:py-6">
        <h2 class="my-4 text-gray-700 dark:text-gray-400 font-semibold">
            服事安排
        </h2>

        <GroupFilterComponent class="my-4" />

        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-orange-300 border border-orange-200 dark:border-none" role="alert" v-show="conflictMembers.length > 0 && !loadingPlan">
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

        <div class="w-full flex items-center justify-between mb-4">
            <div class="inline-flex rounded-md shadow-xs" role="group">
                <button type="button" @click="taskPlanStore.prevPlanMonth"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border border-gray-200 rounded-s-lg focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                    上一月
                </button>
                <button type="button" @click="taskPlanStore.backToCurrentMonth"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border-t border-b border-r border-gray-200 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                    回到 {{ taskPlanStore.now.toFormat('M') }} 月
                </button>
                <button type="button" @click="taskPlanStore.goToNextMonth"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border-t border-b border-gray-200 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                    {{ taskPlanStore.now.plus({ months: 1 }).toFormat('M') }} 月
                </button>
                <button type="button" @click="taskPlanStore.nextPlanMonth"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 border border-gray-200 rounded-e-lg focus:z-10 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
                    下一月
                </button>
            </div>

            <div class="inline-flex">
                <button type="button" @click="refreshTaskPlans"
                    class="text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:hover:bg-primary-600  me-2 mb-2">
                    <svg class="w-4 h-4 me-2 -ms-1 fill-current" :class="{ 'animated': loadingPlan }" aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M142.9 142.9c-17.5 17.5-30.1 38-37.8 59.8c-5.9 16.7-24.2 25.4-40.8 19.5s-25.4-24.2-19.5-40.8C55.6 150.7 73.2 122 97.6 97.6c87.2-87.2 228.3-87.5 315.8-1L455 55c6.9-6.9 17.2-8.9 26.2-5.2s14.8 12.5 14.8 22.2l0 128c0 13.3-10.7 24-24 24l-8.4 0c0 0 0 0 0 0L344 224c-9.7 0-18.5-5.8-22.2-14.8s-1.7-19.3 5.2-26.2l41.1-41.1c-62.6-61.5-163.1-61.2-225.3 1zM16 312c0-13.3 10.7-24 24-24l7.6 0 .7 0L168 288c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6 0-.7 0-7.6z"/></svg>
                    刷新表格
                </button>
            </div>
        </div>

        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-fixed rounded-md">
            <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr class="bg-primary-200 border-b dark:border-gray-700 border-gray-200">
                    <th scope="col" class="w-36 h-12"></th>
                    <template v-for="date in sundays">
                        <th scope="col" class="p-2 w-1/{{ sundays.length }}">
                            <span class="flex w-full gap-x-2 items-center">
                                {{ date.toFormat('yyyy年M月d日') }}
                                <button type="button" title="复制当日服事表文本（PPT用）" @click="taskPlanStore.copyTaskPlanText(date.toISODate() ?? '')">
                                    <svg class="block cursor-pointer w-4 h-4 fill-current text-primary-700 hover:text-primary-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M208 0L332.1 0c12.7 0 24.9 5.1 33.9 14.1l67.9 67.9c9 9 14.1 21.2 14.1 33.9L448 336c0 26.5-21.5 48-48 48l-192 0c-26.5 0-48-21.5-48-48l0-288c0-26.5 21.5-48 48-48zM48 128l80 0 0 64-64 0 0 256 192 0 0-32 64 0 0 48c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 176c0-26.5 21.5-48 48-48z"/></svg>
                                </button>
                            </span>
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template v-for="group in groups">
                    <template v-for="role in group.roles">
                        <tr v-show="isGroupVisible(group)" :style="`background-color: ${group.color}55;`"
                            class="border-b dark:border-gray-700 border-gray-200">
                            <td class="p-2 font-medium text-gray-900 whitespace-nowrap dark:text-white w-auto">
                                <span class="flex w-full gap-x-2 items-center">
                                    {{ role.name }}
                                    <button type="button" title="复制该组服事表文本" @click="copyGroupRolePlansText(role)">
                                        <svg class="block cursor-pointer w-4 h-4 fill-current text-primary-700 hover:text-primary-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M208 0L332.1 0c12.7 0 24.9 5.1 33.9 14.1l67.9 67.9c9 9 14.1 21.2 14.1 33.9L448 336c0 26.5-21.5 48-48 48l-192 0c-26.5 0-48-21.5-48-48l0-288c0-26.5 21.5-48 48-48zM48 128l80 0 0 64-64 0 0 256 192 0 0-32 64 0 0 48c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 176c0-26.5 21.5-48 48-48z"/></svg>
                                    </button>
                                </span>
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
import { useToast } from "vue-toast-notification";

const toastr = useToast();
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

const refreshTaskPlans = () => {
    taskPlanStore.getTaskPlans();
};

const copyGroupRolePlansText = (role: GroupRole) => {
    let text = role.name.replace(/[\r\n]+/g, ' ') + '：\n\n';
    const taskPlan = taskPlanStore.taskPlans.find((plan) => plan.role === role.role);

    if (taskPlan) {
        sundays.value.forEach((date, index) => {
            text += date.toFormat('yyyy年M月d日') + '：\n';
            const plans = taskPlan.plans;
            if (plans) {
                const value = plans['week' + (index + 1).toString()];
                if (value && value !== '') {
                    if (role.type === 'select') {
                        const members = value.split('+').join('、');
                        text += members + '\n';
                    } else {
                        const inputs = value.split('+').join('\n');
                        text += inputs + '\n';
                    }
                }
                text += '\n';
            }
        });

        navigator.clipboard.writeText(text)
            .then(() => toastr.success('服事安排已复制到剪切板'))
            .catch(err => console.error("Failed to copy text: ", err));
    }

    
};

</script>

<style scoped>
    .animated {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>