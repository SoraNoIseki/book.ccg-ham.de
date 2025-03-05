<template>
    <div class="relative shadow-md sm:rounded-lg">
        <p>
            共 {{ groupMembers.length }} 人
        </p>

        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="p-2"></th>
                    <template v-for="group in groups">
                        <th scope="col" :colspan="group.roles.length" class="p-2">
                            {{ group.group }}
                        </th>
                    </template>
                    <th scope="col" class="p-2"></th>
                </tr>
                <tr>
                    <th scope="col" class="p-2">
                        名字
                    </th>
                    <template v-for="group in groups">
                        <template v-for="role in group.roles">
                            <th scope="col" class="p-2">
                                {{ role.name }}
                            </th>
                        </template>
                    </template>
                    <th scope="col" class="p-2">操作</th>
                </tr>
            </thead>
            <tbody>
                <tr  v-for="member in groupMembers"
                    class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
                >
                    <th scope="row" class="p-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ member.name }}
                    </th>

                    <template v-for="group in groups">
                        <template v-for="role in group.roles">
                            <td class="p-2 font-bold text-lg cursor-pointer relative" @click="toggleRole(member, role.role)">
                                <div class="h-full w-full flex items-center justify-center"
                                    @mouseenter="showTooltip(member.name + role.role)"
                                    @mouseleave="hideTooltip(member.name + role.role)"
                                >
                                    <i v-if="isMemberInGroup(member, role.role)" class="w-6 h-6 text-gray-700 fill-current">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                    </i>
                                    <i v-else class="w-6 h-6 text-gray-200 fill-current">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96z"/></svg>
                                    </i>
                                </div>

                                <div :id="'tooltip-member-role' + member.name + role.role" class="absolute min-w-[120px] -translate-x-1/2 left-1/2 -top-4 text-center z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                    <span v-html="role.name"></span>
                                </div>
                            </td>
                        </template>
                    </template>

                    <td class="p-2">
                        <button type="button" @click="deleteMember(member.name)"
                            class="block text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                        >
                            删除
                        </button>
                    </td>
                </tr>
                
            </tbody>
        </table>
    </div>


    <div id="delete-popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <button type="button" @click="closeModal" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">关闭</span>
                </button>
                <div class="p-4 md:p-5 text-center">
                    <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
                        即将删除：<strong>{{ toDelete }}</strong>，删除后无法撤销操作，是否要进行删除？
                    </h3>
                    <button type="button" @click="confirmDelete" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                        删除
                    </button>
                    <button type="button" @click="closeModal" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">取消</button>
                </div>
            </div>
        </div>
    </div>
   


</template>

<script setup lang="ts">

import { ref, Ref, onMounted, nextTick } from 'vue';
import { LoadingIcon, ChevronDownIcon } from '../../icons';
import { useTaskPlanStore } from '../stores';
import { storeToRefs } from 'pinia';
import { Modal } from 'flowbite';

const taskPlanStore = useTaskPlanStore();
const { groupMembers, groups } = storeToRefs(taskPlanStore);

const targetEl: Ref<HTMLElement | null > = ref(null); 
const modal: Ref<Modal | null> = ref(null);

onMounted(async () => {
    targetEl.value = document.getElementById('delete-popup-modal');
    modal.value = new Modal(targetEl.value);
});


const toDelete = ref<string>("");
const deleteMember = (name: string) => {
    toDelete.value = name;
    console.log('toDelete:', toDelete.value);
    if (modal.value) {
        modal.value.show();
    }
};

const confirmDelete = () => {
    console.log('confirmDelete:', toDelete.value);
    taskPlanStore.deleteMember(toDelete.value);
    if (modal.value) {
        modal.value.hide();
    }
};

const closeModal = () => {
    toDelete.value = "";
    if (modal.value) {
        modal.value.hide();
    }
};


const isMemberInGroup = (member: GroupMember, groupRole: string) => {
    return member.roles.some((role: string) => role === groupRole);
};

const toggleRole = (member: GroupMember, role: string) => {
    console.log('toggleRole:', member, role);
    if (member.roles.length === 1 && member.roles[0] === role) {
        console.log('Each member must have at least one role');
        return;
    }
    taskPlanStore.toggleMemberRole(member.name, role);
};

const showTooltip = (id: string) => {
    const tooltip = document.getElementById('tooltip-member-role' + id);
    if (tooltip) {
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


</script>