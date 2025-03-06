<template>
    <div class="space-y-4">
        <div v-for="user in users" :key="user.id" class="p-4 bg-gray-100 rounded-lg">
            <div class="font-bold text-lg">{{ user.name }}</div>
            <div class="mt-2 grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <template v-for="role in roles" :key="role">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" :checked="isToggled(user, role.id)" @click="toggleRole(user.id, role.id)" >
                        <div
                            class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                        </div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ role.name }}</span>
                    </label>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, defineProps, Ref, onMounted } from 'vue';
import axios from 'axios';


interface ApiResponse<T> {
    success: boolean;
    data: T;
}

const users: Ref<any[]> = ref([]);
const roles: Ref<any[]> = ref([]);

onMounted(async () => {
    const [usersResponse, RolesResponse] = await Promise.all([
        axios.get<ApiResponse<any[]>>('/api/admin/users'),
        axios.get<ApiResponse<any[]>>('/api/admin/roles'),
    ]);
    users.value = usersResponse.data.data;
    roles.value = RolesResponse.data.data;
});

const toggleRole = async (userId: number, roleId: number) => {
    await axios.get(`/api/admin/users/${userId}/roles/${roleId}`);
};

const isToggled = (user: any, roleId: number) => {
    return user.roles?.some((role: any) => role.id === roleId);
};

</script>