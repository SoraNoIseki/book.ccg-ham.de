<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import BottomSheet from '../../shared/ui/BottomSheet.vue';
import MessageList from '../../shared/ui/MessageList.vue';
import type { CalendarEvent } from '../types';
import { eventRange } from '../eventRange';

// Edit one entry of the year overview. Several lines of one day are typed on separate lines
// (stored with "|" like in the CSV).
const props = defineProps<{ event: CalendarEvent | null }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const cal = useCalendarStore();
const { isImporting, errors, year } = storeToRefs(cal);

const name = ref('');

// The date is picked as year / month / day (a native date input follows the browser language, not Chinese habits).
// Allowed: the days that can show up in the calendar of the year (see eventRange).
const y = ref(0);
const m = ref(1);
const d = ref(1);

const range = computed(() => eventRange(year.value));
const years = computed(() => [year.value - 1, year.value, year.value + 1].filter((v) => v >= +range.value.min.slice(0, 4) && v <= +range.value.max.slice(0, 4)));
const inRange = (yy: number, mm: number, dd: number): boolean => {
    const iso = `${yy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
    return iso >= range.value.min && iso <= range.value.max;
};
const daysIn = (yy: number, mm: number): number[] =>
    Array.from({ length: new Date(yy, mm, 0).getDate() }, (_, i) => i + 1).filter((dd) => inRange(yy, mm, dd));
const months = computed(() => Array.from({ length: 12 }, (_, i) => i + 1).filter((mm) => daysIn(y.value, mm).length > 0));
const days = computed(() => daysIn(y.value, m.value));

const pad = (n: number): string => String(n).padStart(2, '0');
const date = computed(() => `${y.value}-${pad(m.value)}-${pad(d.value)}`);

// Keep month and day valid when the year or month changes
watch([y, m], () => {
    if (!months.value.includes(m.value)) m.value = months.value[0];
    if (!days.value.includes(d.value)) d.value = days.value.find((v) => v >= d.value) ?? days.value[days.value.length - 1];
});

watch(() => props.event, (ev) => {
    if (!ev) return;
    cal.clearMessages();
    const [ey, em, ed] = ev.date.split('-').map(Number);
    y.value = ey;
    m.value = em;
    d.value = ed;
    name.value = ev.name.replace(/\|/g, '\n');
}, { immediate: true });

async function save(): Promise<void> {
    if (!props.event) return;
    if (await cal.updateEvent(props.event.id, date.value, name.value)) emit('close');
}

const field = 'w-full mt-1 px-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500';
</script>

<template>
    <BottomSheet :open="!!event" title="编辑大事历" @close="emit('close')">
        <form class="space-y-3 pt-1" @submit.prevent="save">
            <MessageList :errors="errors" />
            <fieldset>
                <legend class="text-xs text-gray-500">日期 *</legend>
                <div class="grid grid-cols-3 gap-2">
                    <label class="relative block">
                        <select v-model.number="y" aria-label="年" :class="field + ' h-11 pr-8'">
                            <option v-for="v in years" :key="v" :value="v">{{ v }} 年</option>
                        </select>
                    </label>
                    <label class="relative block">
                        <select v-model.number="m" aria-label="月" :class="field + ' h-11 pr-8'">
                            <option v-for="v in months" :key="v" :value="v">{{ v }} 月</option>
                        </select>
                    </label>
                    <label class="relative block">
                        <select v-model.number="d" aria-label="日" :class="field + ' h-11 pr-8'">
                            <option v-for="v in days" :key="v" :value="v">{{ v }} 日</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <label class="block text-xs text-gray-500">事件 *
                <textarea v-model="name" rows="4" required :class="field + ' py-2'"></textarea>
                <span class="block mt-1 text-gray-400">同一天有多项内容时，每项单独一行。</span>
            </label>
            <div class="flex gap-2 pt-2">
                <button type="button" @click="emit('close')"
                    class="h-12 px-5 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 active:scale-95 transition">取消</button>
                <button type="submit" :disabled="isImporting || !name.trim()"
                    class="flex-1 h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/25 disabled:opacity-50 active:scale-95 transition">
                    {{ isImporting ? '保存中...' : '保存' }}
                </button>
            </div>
        </form>
    </BottomSheet>
</template>
