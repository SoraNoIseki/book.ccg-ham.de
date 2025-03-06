<template>
    <div class="relative py-2 sm:px-4 sm:py-6">
        <h2 class="my-4 text-gray-700 dark:text-gray-400 font-semibold">
            服事安排
        </h2>

        <GroupFilterComponent class="my-4" />

        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-fixed rounded-md overflow-hidden">
            <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr class="bg-primary-200 border-b dark:border-gray-700 border-gray-200">
                    <th scope="col" class="w-36 h-12"></th>
                    <template v-for="date in sundays">
                        <th scope="col" class="p-2 w-1/{{ sundays.length }}">{{ date.toISODate() }}</th>
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
                                    <div class="h-full w-full flex items-center justify-center">
                                        <UiMultiSelect v-if="role.type === 'select'"
                                            :placeholder="role.placeholder ?? '请选择'"
                                            v-model="planForm[role.role]['week' + (index + 1).toString()]"
                                            :options="selectItemsByRole(role.role)" :max="role.max"
                                            :disabled="!isUserHasPermission(group.permission)"
                                            @change="onChangeMembers($event, role.role, date)">
                                        </UiMultiSelect>

                                        <div v-if="role.type === 'input'" class="flex items-center justify-center h-full w-full">
                                            <textarea rows="3" v-model="planForm[role.role]['week' + (index + 1).toString()]"
                                                :placeholder="role.placeholder ?? '请输入'"
                                                :disabled="!isUserHasPermission(group.permission)"
                                                @change="onInput($event, role.role, date)"
                                                class="block p-2.5 w-full text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"></textarea>
                                        </div>
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


const sundays = ref<DateTime[]>([]);

const taskPlanStore = useTaskPlanStore();
const { groups, sortedMembersByRole, planForm, groupFilter } = storeToRefs(taskPlanStore);


onMounted(async () => {
    // find all Sundays in the month
    const now = DateTime.now();
    const daysInMonth = now.daysInMonth;
    for (let day = 1; day <= daysInMonth; day++) {
        const date = DateTime.local(now.year, now.month, day);
        if (date.weekday === 7) {
            sundays.value.push(date);
        }
    }
});

const selectItemsByRole = computed(() => {
    return (role: string) => {
        return sortedMembersByRole.value.find((item: any) => item.role === role)?.names.map((name) => {
            return { value: name, label: name };
        }) || [];
    };
});

const onChangeMembers = (value: string[], role: string, date: DateTime) => {
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

</script>