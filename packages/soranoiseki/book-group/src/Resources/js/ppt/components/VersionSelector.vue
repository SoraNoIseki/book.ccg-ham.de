<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../store/powerpoint';

const ppt = usePowerpointStore();
const { date, selectedDate, versionOptions, isLoading, saveEnabled } = storeToRefs(ppt);

// Load immediately when another version is picked.
function onChange(): void {
    ppt.loadVersion(selectedDate.value);
}
</script>

<template>
    <div class="flex flex-wrap -mx-3">
        <!-- Current date (read-only) -->
        <div class="w-full px-3 sm:w-1/2">
            <div class="mb-5">
                <label for="ppt-date" class="mb-3 block text-base font-medium text-main">日期</label>
                <input id="ppt-date" type="date" disabled :value="date"
                    class="w-full rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none disabled:bg-gray-50" />
                <p class="mt-2 text-sm" :class="saveEnabled ? 'text-green-700' : 'text-gray-500'">
                    {{ saveEnabled ? '本周版本，可编辑' : '历史版本，仅可下载' }}
                </p>
            </div>
        </div>

        <!-- Version selector -->
        <div class="w-full px-3 sm:w-1/2">
            <div class="mb-5">
                <label for="ppt-version" class="mb-3 block text-base font-medium text-main">加载其他版本</label>
                <select id="ppt-version" v-model="selectedDate" :disabled="isLoading" @change="onChange"
                    class="w-full rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md disabled:opacity-50">
                    <option v-for="o in versionOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
            </div>
        </div>
    </div>
</template>
