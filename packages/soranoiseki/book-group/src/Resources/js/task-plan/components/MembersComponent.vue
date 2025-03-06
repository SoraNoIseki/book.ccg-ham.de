<template>
    <div class="relative py-2 sm:px-4 sm:py-6">
        <h2 class="my-4 text-gray-700 dark:text-gray-400 font-semibold">
            组员管理（共 {{ groupMembers.length }} 人）
        </h2>

        <p class="my-4 text-gray-700 dark:text-gray-400 font-semibold text-sm">
            请将组员分配到对应的小组和角色中，每个组员至少有一个角色。已分配的组员名字将会作为选项显示在计划表中。<br>
            添加新组员请前往<span @click="scrollToId('add-member-component')" class="text-primary-600 hover:underline px-0.5 cursor-pointer">添加组员</span>模块。
        </p>

        <GroupFilterComponent class="my-4" />
        
        <div class="relative mb-4">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
            </div>
            <input type="search" v-model="search" placeholder="搜索名字..."
                class="block w-full px-4 py-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" />
        </div>

        <table class="w-full text-sm text-left rtl:text-right text-gray-700 dark:text-gray-400 rounded-md overflow-hidden">
            <thead class="text-sm font-semibold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 text-center">
                <tr>
                    <th scope="col" class="p-2 bg-primary-400"></th>
                    <template v-for="group in groups">
                        <th scope="col" :colspan="group.roles.filter(role => role.type === 'select').length" class="p-2" v-show="isGroupVisible(group)" :style="`background-color: ${group.color}55;`">
                            {{ group.group }}
                        </th>
                    </template>
                    <th scope="col" class="p-2 bg-red-500"></th>
                </tr>
                <tr class="border-b dark:border-gray-700 border-gray-200">
                    <th scope="col" class="py-2 px-4 bg-primary-400 text-left">
                        名字
                    </th>
                    <template v-for="group in groups">
                        <template v-for="role in group.roles">
                            <th scope="col" class="p-2" 
                                v-if="role.type === 'select'"
                                v-show="isGroupVisible(group)" 
                                :style="`background-color: ${group.color}55;`">
                                {{ role.name }}
                            </th>
                        </template>
                    </template>
                    <th scope="col" class="p-2 bg-red-500 text-white">操作</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="member in groupMembers" :id="member.name" :key="member.name" v-show="isMemberShown(member)"
                    class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200">
                    <th scope="row" class="py-2 px-4 font-medium text-gray-900 whitespace-nowrap dark:text-white bg-primary-400">
                        {{ member.name }}
                    </th>

                    <template v-for="group in groups">
                        <template v-for="role in group.roles">
                            <td v-if="role.type === 'select'"
                                class="p-2 font-bold text-lg relative" 
                                :style="`background-color: ${group.color}55; color: ${group.color};`"
                                :class="[ isUserHasPermission(group.permission) ? 'cursor-pointer' : 'cursor-not-allowed' ]"
                                v-show="isGroupVisible(group)"
                                @click="toggleRole(member, role.role, group.permission)">
                                <div class="h-full w-full flex items-center justify-center"
                                    @mouseenter="showTooltip(member.name + role.role, group.color)"
                                    @mouseleave="hideTooltip(member.name + role.role)">
                                    <i v-if="isMemberInGroup(member, role.role)"
                                        class="w-6 h-6 fill-current">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                            <path
                                                d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" />
                                        </svg>
                                    </i>
                                    <i v-else class="w-6 h-6 fill-current opacity-70">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                            <path
                                                d="M0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96z" />
                                        </svg>
                                    </i>
                                </div>

                                <div :id="'tooltip-member-role' + member.name + role.role" :style="`background-color: ${tooltipBgColor ?? '#333'};`"
                                    class="absolute min-w-[120px] -translate-x-1/2 left-1/2 -top-5 text-center z-10 invisible inline-block px-3 py-2 text-xs font-medium text-white rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                    <span v-html="role.name"></span>
                                </div>
                            </td>
                        </template>
                    </template>

                    <td class="p-2 bg-red-500 text-white">
                        <button type="button" @click="onDeleteMember(member)" :disabled="!isUserHasEditPermission"
                            class="block text-white bg-red-500 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-1.5 py-2.5 w-full text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            删除
                        </button>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    <div id="delete-popup-modal" tabindex="-1"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <button type="button" @click="closeDeleteModal"
                    class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">关闭</span>
                </button>
                <div class="p-4 md:p-5 text-center">
                    <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <h3 class="mb-5 text-lg font-normal text-gray-700 dark:text-gray-400">
                        即将删除：<strong>{{ toDelete }}</strong>，删除后无法撤销操作，是否要进行删除？
                    </h3>
                    <button type="button" @click="confirmDelete"
                        class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-base px-5 py-2.5 text-center w-full mb-4">
                        删除
                    </button>
                    <button type="button" @click="closeDeleteModal"
                        class="w-full px-5 py-2.5 mb-4 text-base font-medium text-gray-900 focus:outline-none bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">取消</button>
                </div>
            </div>
        </div>
    </div>

    <div id="delete-error-popup-modal" tabindex="-1"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <button type="button" @click="closeErrorModal"
                    class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">关闭</span>
                </button>
                <div class="p-4 md:p-5 text-center">
                    <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <h3 class="mb-5 text-lg font-normal text-gray-700 dark:text-gray-400">
                        <span v-html="errorMessage"></span>
                    </h3>
                    <button type="button" @click="closeErrorModal"
                        class="w-full px-5 py-2.5 mb-4 text-base font-medium text-gray-900 focus:outline-none bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">关闭</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup lang="ts">

import { ref, Ref, onMounted } from 'vue';
import { useTaskPlanStore } from '../stores';
import { storeToRefs } from 'pinia';
import { Modal } from 'flowbite';
import { GroupFilterComponent } from './';
import { TaskPlanService } from '../services';

const taskPlanStore = useTaskPlanStore();
const { groupMembers, groups, groupFilter, isUserHasEditPermission } = storeToRefs(taskPlanStore);

const deleteTargetEl: Ref<HTMLElement | null> = ref(null);
const deleteModal: Ref<Modal | null> = ref(null);
const errorTargetEl: Ref<HTMLElement | null> = ref(null);
const errorModal: Ref<Modal | null> = ref(null);
const errorMessage = ref<string>('');

const search = ref<string>("");
const tooltipBgColor = ref<string>("");

onMounted(async () => {
    deleteTargetEl.value = document.getElementById('delete-popup-modal');
    deleteModal.value = new Modal(deleteTargetEl.value);
    errorTargetEl.value = document.getElementById('delete-error-popup-modal');
    errorModal.value = new Modal(errorTargetEl.value);
});

const toDelete = ref<string>('');
const onDeleteMember = (member: GroupMember) => {
    const isUserCanDeleteMember = TaskPlanService.isUserCanDeleteMember(member, groups.value);

    if (!isUserCanDeleteMember) {
        return;
    }

    if (Array.isArray(isUserCanDeleteMember) && isUserCanDeleteMember.length > 0) {
        const errorGroupNames = [...new Set(isUserCanDeleteMember.map((group: Group) => group.group))];
        errorMessage.value = `组员 <strong>${member.name}</strong> 无法删除，因为他/她同时也是 <strong>${errorGroupNames.join('、')}</strong> 的成员。`;
        if (errorModal.value) {
            errorModal.value.show();
        }
        return;
    }

    toDelete.value = member.name;
    if (deleteModal.value) {
        deleteModal.value.show();
    }
};

const confirmDelete = () => {
    taskPlanStore.deleteMember(toDelete.value);
    if (deleteModal.value) {
        deleteModal.value.hide();
    }
};

const closeDeleteModal = () => {
    toDelete.value = "";
    if (deleteModal.value) {
        deleteModal.value.hide();
    }
};

const closeErrorModal = () => {
    if (errorModal.value) {
        errorModal.value.hide();
    }
};

const isMemberInGroup = (member: GroupMember, groupRole: string) => {
    return member.roles.some((role: string) => role === groupRole);
};

const toggleRole = (member: GroupMember, role: string, groupPermission: string) => {
    if (!isUserHasPermission(groupPermission)) {
        return;
    }

    if (member.roles.length === 1 && member.roles[0] === role) {
        console.log('Each member must have at least one role');
        return;
    }

    taskPlanStore.toggleMemberRole(member.name, role);
};

const showTooltip = (id: string, color: string) => {
    const tooltip = document.getElementById('tooltip-member-role' + id);
    if (tooltip) {
        tooltipBgColor.value = color;
        tooltip.classList.remove('invisible', 'opacity-0');
        tooltip.classList.add('visible', 'opacity-100');
    }
};

const hideTooltip = (id: string) => {
    const tooltip = document.getElementById('tooltip-member-role' + id);
    if (tooltip) {
        tooltip.classList.remove('visible', 'opacity-100');
        tooltip.classList.add('invisible', 'opacity-0');
    }
};

const scrollToId = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const isGroupVisible = (group: Group) => {
    return groupFilter.value.find((filter: GroupFilterItem) => filter.name === group.group)?.enabled ?? true;
};

const isMemberShown = (member: GroupMember) => {
    return member.name.toLowerCase().includes(search.value.toLowerCase());
};

const isUserHasPermission = (permission: string) => {
    return TaskPlanService.isUserHasPermission(permission);
};

</script>