<template>
    <div class="relative shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-fixed">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="p-2 w-auto"></th>
                    <template v-for="date in sundays">
                        <th scope="col" class="p-2 w-1/{{ sundays.length }}">{{ date.toISODate() }}</th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template v-for="group in groups">
                    <template v-for="role in group.roles">
                        <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200">
                            <td class="p-2 font-medium text-gray-900 whitespace-nowrap dark:text-white w-auto">
                                {{ role.name }}
                            </td>
                            <template v-for="(date, index) in sundays">
                                <td class="p-2 font-bold text-lg cursor-pointer relative w-1/{{ sundays.length }}">
                                    <div class="h-full w-full flex items-center justify-center">
                                        <UiMultiSelect
                                            placeholder="请选择"
                                            v-model="planForm[role.role]['week' + (index + 1).toString()]"
                                            :options="selectItemsByRole(role.role)"
                                            @change="onChange($event, role.role, date, index)"
                                            class="mb-4"
                                        ></UiMultiSelect>
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
import { LoadingIcon, ChevronDownIcon } from '../../icons';
import { useTaskPlanStore } from '../stores';
import { storeToRefs } from 'pinia';
import { DateTime } from 'luxon';
import { UiMultiSelect } from './';


const sundays = ref<DateTime[]>([]);

const taskPlanStore = useTaskPlanStore();
const { groupMembers, groups, sortedMembersByRole, planForm } = storeToRefs(taskPlanStore);


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

    console.log('Sundays in this month:', sundays.value.map(date => date.toISODate()));
});


const selectItemsByRole = computed(() => {
    return (role: string) => {
        return sortedMembersByRole.value.find((item: any) => item.role === role)?.names.map((name) => {
            return { value: name, label: name };
        }) || [];
    };
});

const onChange = (value: string[], role: string, date: DateTime, index: number) => {
    console.log('onChange:', value, role, date);
    taskPlanStore.updateTaskPlan(role, value.join('+'), date.toISODate() ?? '');
};

</script>