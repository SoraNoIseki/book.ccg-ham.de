<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../../store/powerpoint';
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';

const ppt = usePowerpointStore();
const { date, nextWeek, endpoints } = storeToRefs(ppt);
const loading = ref<string | null>(null);

// Fill a worker item with the generated service plan text of the given date.
async function loadPlan(item: 'item1' | 'item2', planDate: string): Promise<void> {
    if (!planDate || loading.value) return;
    loading.value = item;
    try {
        const { data } = await axios.get(endpoints.value.taskText, { params: { date: planDate } });
        const text = data?.data?.text;
        if (text !== undefined) ppt.setField('worker', item, text);
    } catch {
        ppt.errors = ['无法加载主日服事表，请稍后重试。'];
    } finally {
        loading.value = null;
    }
}
</script>

<template>
    <ContentField tab="worker" item="item1" label="服事列表（本周）" :rows="12">
        <template #toolbar>
            <button type="button" :disabled="!!loading" @click="loadPlan('item1', date)"
                class="mb-3 inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 hover:bg-primary-100/60 active:scale-95 transition-all duration-300 outline-none focus-visible:ring-2 focus-visible:ring-primary-600 disabled:opacity-50 disabled:cursor-not-allowed dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700">
                <svg v-if="loading === 'item1'" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
                <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                </svg>
                {{ loading === 'item1' ? '加载中...' : '加载 ' + date + ' 主日服事表' }}
            </button>
        </template>
        <template #hint>
            <AlertBox title="格式范例：">
                <li>2023 年 08 月 16 日主日服事表</li>
                <li>主题：善仆与恶仆<span class="font-bold text-red-600">+</span>路19:11-28</li>
                <li>证道：吴振忠牧师</li>
                <li>司会：XXX弟兄</li>
                <li>司琴：XXX姊妹</li>
                <li>...</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>主题中<span class="font-semibold mx-1">必须</span>包含英文加号，用于分隔主题和经文</li>
                <li>数字前后建议加上空格</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="worker" item="item2" label="服事列表（下周）" :rows="12">
        <template #toolbar>
            <button type="button" :disabled="!!loading" @click="loadPlan('item2', nextWeek)"
                class="mb-3 inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 hover:bg-primary-100/60 active:scale-95 transition-all duration-300 outline-none focus-visible:ring-2 focus-visible:ring-primary-600 disabled:opacity-50 disabled:cursor-not-allowed dark:text-primary-200 dark:bg-gray-800 dark:ring-primary-400/50 dark:hover:bg-gray-700">
                <svg v-if="loading === 'item2'" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
                <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                </svg>
                {{ loading === 'item2' ? '加载中...' : '加载 ' + nextWeek + ' 主日服事表' }}
            </button>
        </template>
    </ContentField>
</template>
