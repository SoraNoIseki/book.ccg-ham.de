<template>
    <div class="relative p-2 sm:px-4 sm:py-6">
        <h2 class="my-4 text-gray-700 dark:text-gray-400 font-semibold">
            快捷操作：添加组员
        </h2>

        <p class="my-4 text-gray-700 dark:text-gray-400 font-semibold text-sm">
            请仔细检查组员名字，确保名字正确无误。已添加的组员名字将会作为选项显示在计划表中。<br>
            管理组员请前往<span  @click="scrollToId('members-component')" class="text-primary-600 hover:underline px-0.5 cursor-pointer">组员管理</span>模块。
        </p>

        <div class="flex items-center mb-4 bg-white dark:bg-gray-700 dark:text-gray-400 gap-x-4">
            <div class="">
                <input type="text" v-model="toAddMember" placeholder="请输入名字" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
            </div>

            <UiSingleSelect placeholder="无称谓"
                v-model="toAddTitle"
                :options="titles"
                :searchable="false"
                class="max-w-[150px]">
            </UiSingleSelect>

            <UiSingleSelect placeholder="请选择小组"
                v-model="toAddRole"
                :options="roles"
                :searchable="false"
                :groups="true"
                @clear="onClearCreateMemberRole"
                class="max-w-[200px]">
            </UiSingleSelect>

            <button type="button" @click="onAddMember" :disabled="!enableAddButton"
                class="text-white bg-primary-600 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-700 dark:hover:bg-primary-800 dark:focus:ring-primary-800 disabled:opacity-50 disabled:cursor-not-allowed">
                添加组员
            </button>
        </div>
        <div>
            <p class="my-4 text-gray-700 dark:text-gray-400 font-semibold" v-if="previewMemberName">
                <span v-if="nameValid">预览：名字 <span class="text-green-600">{{ previewMemberName }}</span> 将被添加到 {{ getGroupRoleLabel(toAddRole) }}</span>
                <span v-else class="text-red-700">
                    <span>错误：{{ previewMemberName }}</span>
                    <span v-if="redirectToMember !== ''" @click="scrollToId(redirectToMember)">
                        立即前往<span class="text-primary-600 hover:underline px-0.5 cursor-pointer">组员编辑</span>模块
                    </span>
                </span>
            </p>
            <p class="my-4 text-gray-700 dark:text-gray-400 font-semibold text-sm" v-if="possibleMemberNames.length > 0">
                <p class="mb-2">你是否想添加：</p>
                <p v-for="name in possibleMemberNames" :key="name" class="mb-1">
                    {{ name }}
                    <span class="mx-2">|</span>
                    <span>
                        立即前往<span class="text-primary-600 hover:underline px-0.5 cursor-pointer" @click="scrollToId(name)">组员编辑</span>模块
                    </span>
                </p>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">

import { ref, Ref, onMounted, computed } from 'vue';
import { useTaskPlanStore } from '../stores';
import { storeToRefs } from 'pinia';
import { UiSingleSelect } from '.';
import { TaskPlanService } from '../services';

const taskPlanStore = useTaskPlanStore();
const { groups, groupMembers } = storeToRefs(taskPlanStore);

onMounted(async () => {

});

const roles: Ref<SelectOptionGroup[]> = computed(() => {
    return groups.value.reduce((acc: SelectOptionGroup[], group: Group) => {
        if (TaskPlanService.isUserHasPermission('planer_admin') || TaskPlanService.isUserHasPermission(group.permission)) {
            group.roles.forEach((role: GroupRole) => {
                const groupIndex = acc.findIndex((g: SelectOptionGroup) => g.label === group.group);
                if (groupIndex === -1) {
                    acc.push({
                        label: group.group,
                        options: [{
                            value: role.role,
                            label: role.name,
                        }],
                    });
                } else {
                    acc[groupIndex].options.push({
                        value: role.role,
                        label: role.name,
                    });
                }
            });
        }
        return acc;
    }, []);
});

const titles: SelectOption[] = [
    { value: '弟兄', label: '弟兄' },
    { value: '姊妹', label: '姊妹' },
    { value: '牧师', label: '牧师' },
    { value: '师母', label: '师母' },
    { value: '传道', label: '传道' },
];

const toAddMember = ref<string>("");
const toAddTitle = ref<string>("");
const toAddRole = ref<string>("");
const enableAddButton = computed(() => {
    return toAddMember.value.length > 0 && toAddRole.value.length > 0 && nameValid.value;
});

const onAddMember = () => {
    if (!enableAddButton.value) {
        return;
    }
    taskPlanStore.addMember(previewMemberName.value, toAddRole.value);

    toAddMember.value = "";
    toAddTitle.value = "";
    toAddRole.value = "";
};

const onClearCreateMemberRole = () => {
    toAddRole.value = "";
};  

const previewMemberName = computed(() => {
    return buildMemberName();
});
const nameValid = ref<boolean>(true);
const redirectToMember = ref<string>('');
const possibleMemberNames = ref<string[]>([]);

const buildMemberName = () => {
    let memberName = toAddMember.value.trim();
    const titleRegex = new RegExp(titles.map((t: SelectOption) => t.value).join('|'), 'g');
    possibleMemberNames.value = [];

    if (memberName === '') {
        return '';
    }

    // Check if the name contains English characters
    const containsEnglish = /[a-zA-Z]/.test(memberName);

    if (!containsEnglish) {
        // Remove all spaces and punctuation marks
        memberName = memberName.replace(/[\s\p{P}]/gu, '');

        // Find possible names
        possibleMemberNames.value = groupMembers.value.filter((member: GroupMember) => member.name.replace(/[\s\p{P}]/gu, '').includes(memberName)).map((member: GroupMember) => member.name);

        if (memberName.length === 1) {
            nameValid.value = false;
            return '请输入完整名字';
        }

        // If name match title regex
        if (toAddTitle.value !== '' && titleRegex.test(memberName)) {
            nameValid.value = false;
            return '名字中不能包含称谓';
        }

        // Check if the name already exists
        const existing = groupMembers.value.find((member: GroupMember) => {
            let name = member.name.replace(/[\s\p{P}]/gu, '');
            if (toAddTitle.value === '') {
                name = name.replace(titleRegex, '');
            }
            return name === (memberName + toAddTitle.value);
        });
        if (existing) {
            nameValid.value = false;
            redirectToMember.value = existing.name;
            return `名字 ${existing.name} 已存在。`;
        }

        // If the name contains Chinese characters, add a space between each character
        if (memberName.length === 2 && toAddTitle.value !== '') {
            memberName = memberName[0] + '　' + memberName[1];
        }

        // Add title
        if (toAddTitle.value) {
            memberName += toAddTitle.value;
        }
    } else {
        // Remove spaces at the beginning and end
        memberName = memberName.trim();

        // Find possible names
        possibleMemberNames.value = groupMembers.value.filter((member: GroupMember) => member.name.replace(/[\s\p{P}]/gu, '').toLowerCase().includes(memberName.toLowerCase())).map((member: GroupMember) => member.name);

        if (memberName.length < 3) {
            nameValid.value = false;
            return '请输入完整名字';
        }

        // If name match title regex
        if (toAddTitle.value !== '' && titleRegex.test(memberName)) {
            nameValid.value = false;
            return '名字中不能包含称谓';
        }

        // Check if the name already exists
        const existing = groupMembers.value.find((member: GroupMember) => {
            let name = member.name.replace(/[\s\p{P}]/gu, '').toLowerCase();
            if (toAddTitle.value === '') {
                name = name.replace(titleRegex, '');
            }
            return name === (memberName.toLowerCase() + toAddTitle.value);
        });
        if (existing) {
            nameValid.value = false;
            redirectToMember.value = existing.name;
            return `名字 ${existing.name} 已存在。`;
        }

        // Add title
        if (toAddTitle.value) {
            memberName += ' ' + toAddTitle.value;
        }
    }

    nameValid.value = true;
    redirectToMember.value = '';
    return memberName;
};

const scrollToId = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const getGroupRoleLabel = (role: string): string => {
    return taskPlanStore.groupRoles.find((item) => item.role === role)?.name || role;
}

</script>