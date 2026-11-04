<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '@book/ppt/store/powerpoint';
import type { TabId } from '../types';

import TabPray from './tabs/TabPray.vue';
import TabScripture from './tabs/TabScripture.vue';
import TabPreach from './tabs/TabPreach.vue';
import TabSong from './tabs/TabSong.vue';
import TabReport from './tabs/TabReport.vue';
import TabWorker from './tabs/TabWorker.vue';

const ppt = usePowerpointStore();
const { saveEnabled, activeTab, isSubmitting, canDownload } = storeToRefs(ppt);

const tabs: Array<{ id: TabId; label: string }> = [
    { id: 'pray', label: '代祷' },
    { id: 'scripture', label: '经文' },
    { id: 'preach', label: '讲道' },
    { id: 'song', label: '诗歌' },
    { id: 'report', label: '报告' },
    { id: 'worker', label: '服事' },
];
</script>

<template>
    <!-- Actions moved above tabs -->
    <div class="mb-4 flex gap-3">
        <template v-if="saveEnabled">
            <button type="button" :disabled="isSubmitting" @click="ppt.save()"
                class="hover:shadow-form rounded-md bg-primary-600 py-3 px-8 text-center text-base font-semibold text-white outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                保存
            </button>
            <button type="button" :disabled="isSubmitting || !canDownload" :title="canDownload ? '' : '请先填写所有必填内容（报告除外）'" @click="ppt.saveAndDownload()"
                class="hover:shadow-form rounded-md bg-primary-700 py-3 px-8 text-center text-base font-semibold text-white outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                保存并下载
            </button>
        </template>

        <template v-else>
            <button type="button" :disabled="isSubmitting" @click="ppt.download()"
                class="hover:shadow-form rounded-md bg-primary-700 py-3 px-8 text-center text-base font-semibold text-white outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                下载
            </button>
        </template>
    </div>

    <!-- Tabs -->
    <div>
        <div class="flex flex-wrap gap-2 mb-4">
            <button v-for="t in tabs" :key="t.id" type="button" @click="ppt.setActiveTab(t.id)" :class="[
                'px-4 py-2 rounded-md text-sm font-semibold',
                activeTab === t.id ? 'bg-primary-600 text-white' : 'bg-gray-100 text-main',
            ]">
                {{ t.label }}
            </button>
        </div>

        <!-- Tab panels -->
        <div v-show="activeTab === 'pray'">
            <TabPray />
        </div>
        <div v-show="activeTab === 'scripture'">
            <TabScripture />
        </div>
        <div v-show="activeTab === 'preach'">
            <TabPreach />
        </div>
        <div v-show="activeTab === 'song'">
            <TabSong />
        </div>
        <div v-show="activeTab === 'report'">
            <TabReport />
        </div>
        <div v-show="activeTab === 'worker'">
            <TabWorker />
        </div>
    </div>
</template>