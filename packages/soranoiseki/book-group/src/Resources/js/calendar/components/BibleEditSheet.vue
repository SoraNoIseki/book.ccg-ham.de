<script setup lang="ts">
import { ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import BottomSheet from '../../shared/ui/BottomSheet.vue';
import MessageList from '../../shared/ui/MessageList.vue';

// Edit (or set) the verse of one month. Lines are typed on separate lines (stored with "|" like in the CSV).
const props = defineProps<{ month: number | null; text: string; source: string }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const cal = useCalendarStore();
const { isImporting, errors } = storeToRefs(cal);

const text = ref('');
const source = ref('');
/** Kept while the sheet fades out, so that the title does not change */
const shown = ref(0);

watch(() => props.month, (m) => {
    if (m === null) return;
    cal.clearMessages();
    shown.value = m;
    text.value = props.text.replace(/\|/g, '\n');
    source.value = props.source;
}, { immediate: true });

async function save(): Promise<void> {
    if (props.month === null) return;
    if (await cal.updateBibleText(props.month, text.value, source.value)) emit('close');
}

const field = 'w-full mt-1 px-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500';
</script>

<template>
    <BottomSheet :open="month !== null" :title="`编辑 ${shown} 月经文`" @close="emit('close')">
        <form class="space-y-3 pt-1" @submit.prevent="save">
            <MessageList :errors="errors" />
            <label class="block text-xs text-gray-500">经文 *
                <textarea v-model="text" rows="5" required :class="field + ' py-2'"></textarea>
                <span class="block mt-1 text-gray-400">每行对应图片下方的一行文字。</span>
            </label>
            <label class="block text-xs text-gray-500">出处
                <input v-model="source" type="text" maxlength="255" placeholder="例如：帖撒罗尼迦前书 5章16-18节"
                    :class="field + ' h-11'" />
            </label>
            <div class="flex gap-2 pt-2">
                <button type="button" @click="emit('close')"
                    class="h-12 px-5 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 active:scale-95 transition">取消</button>
                <button type="submit" :disabled="isImporting || !text.trim()"
                    class="flex-1 h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                    {{ isImporting ? '保存中...' : '保存' }}
                </button>
            </div>
        </form>
    </BottomSheet>
</template>
