<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../store/powerpoint';

const ppt = usePowerpointStore();
const { errors, infoMessage, successMessage } = storeToRefs(ppt);
</script>

<template>
    <div v-if="errors.length || infoMessage || successMessage" class="space-y-2">
        <div v-if="errors.length" role="alert"
            class="flex gap-3 p-4 text-sm text-red-800 rounded-xl bg-red-50 ring-1 ring-red-200 dark:text-red-200 dark:bg-red-950/40 dark:ring-red-900">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z" />
            </svg>
            <div>
                <div v-for="(e, i) in errors" :key="i">{{ e }}</div>
            </div>
        </div>
        <div v-if="successMessage" role="status"
            class="flex gap-3 p-4 text-sm text-green-800 rounded-xl bg-green-50 ring-1 ring-green-200 dark:text-green-200 dark:bg-green-950/40 dark:ring-green-900">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.2 14.4-4-4 1.4-1.4 2.6 2.6 5-5 1.4 1.4-6.4 6.4Z" />
            </svg>
            <div>{{ successMessage }}</div>
        </div>
        <div v-if="infoMessage" role="status"
            class="flex gap-3 p-4 text-sm text-blue-800 rounded-xl bg-blue-50 ring-1 ring-blue-200 dark:text-blue-200 dark:bg-blue-950/40 dark:ring-blue-900">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 15h-2v-6h2v6Zm0-8h-2V7h2v2Z" />
            </svg>
            <div>{{ infoMessage }}</div>
        </div>
    </div>
</template>
