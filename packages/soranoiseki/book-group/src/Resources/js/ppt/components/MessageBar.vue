<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../store/powerpoint';

const ppt = usePowerpointStore();
const { errors, infoMessage, successMessage } = storeToRefs(ppt);
</script>

<template>
    <div v-if="errors.length || infoMessage || successMessage" class="mb-4 space-y-2">
        <div v-if="errors.length" role="alert"
            class="p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50">
            <div v-for="(e, i) in errors" :key="i">{{ e }}</div>
        </div>
        <div v-if="successMessage" role="status"
            class="p-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50">
            {{ successMessage }}
        </div>
        <div v-if="infoMessage" role="status"
            class="p-4 text-sm text-blue-800 border border-blue-300 rounded-lg bg-blue-50">
            {{ infoMessage }}
        </div>
    </div>
</template>
