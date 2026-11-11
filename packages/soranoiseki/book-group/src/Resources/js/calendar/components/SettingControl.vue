<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { SettingItem, SettingValue } from '../types';

// One design setting rendered from its schema entry (color, number, select or toggle).
const props = defineProps<{ item: SettingItem; modelValue: SettingValue }>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: SettingValue): void;
    (e: 'reset'): void;
}>();

const changed = computed(() => props.modelValue !== props.item.default);

// ---- color: native picker plus a hex field that only commits valid values ----
const hex = ref(String(props.modelValue));
const hexInvalid = ref(false);
watch(() => props.modelValue, (v) => {
    hex.value = String(v);
    hexInvalid.value = false;
});

function onHex(value: string): void {
    hex.value = value;
    const v = value.trim().toLowerCase();
    hexInvalid.value = !/^#[0-9a-f]{6}$/.test(v);
    if (!hexInvalid.value) emit('update:modelValue', v);
}

// ---- number: slider and number field, always clamped to the schema range ----
function setNumber(raw: string | number): void {
    let n = Number(raw);
    if (Number.isNaN(n)) return;
    n = Math.min(Math.max(n, props.item.min ?? n), props.item.max ?? n);
    const step = props.item.step ?? 1;
    // avoid floating point noise such as 2.4000000000000004
    emit('update:modelValue', Number((Math.round(n / step) * step).toFixed(4)));
}
</script>

<template>
    <div class="py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
        <div class="flex items-center justify-between gap-3">
            <label class="text-sm font-medium text-main dark:text-gray-100">{{ item.label }}</label>
            <button v-if="changed" type="button" title="恢复默认" @click="emit('reset')"
                class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-primary-700 dark:hover:text-primary-300 transition">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5" />
                </svg>
                默认
            </button>
        </div>

        <div class="mt-2">
            <!-- color -->
            <div v-if="item.type === 'color'" class="flex items-center gap-3">
                <input type="color" :value="String(modelValue)" @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
                    class="h-9 w-12 p-0.5 rounded-lg border border-gray-200 bg-white cursor-pointer" :aria-label="item.label" />
                <input type="text" :value="hex" maxlength="7" spellcheck="false" @input="onHex(($event.target as HTMLInputElement).value)"
                    class="w-28 h-9 px-3 rounded-lg border text-sm font-mono outline-none focus:ring-2"
                    :class="hexInvalid ? 'border-red-300 focus:ring-red-200' : 'border-gray-200 focus:ring-primary-500/40 focus:border-primary-500'" />
                <span v-if="hexInvalid" class="text-xs text-red-600">格式应为 #rrggbb</span>
            </div>

            <!-- number -->
            <div v-else-if="item.type === 'number'" class="flex items-center gap-3">
                <input type="range" :min="item.min" :max="item.max" :step="item.step" :value="Number(modelValue)"
                    @input="setNumber(($event.target as HTMLInputElement).value)" class="flex-1 accent-primary-600" :aria-label="item.label" />
                <div class="flex items-center gap-1">
                    <input type="number" :min="item.min" :max="item.max" :step="item.step" :value="Number(modelValue)"
                        @change="setNumber(($event.target as HTMLInputElement).value)"
                        class="w-20 h-9 px-2 rounded-lg border border-gray-200 text-sm text-right tabular-nums outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500" />
                    <span v-if="item.unit" class="text-xs text-gray-400 w-7">{{ item.unit }}</span>
                </div>
            </div>

            <!-- select -->
            <div v-else-if="item.type === 'select'" class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900" role="radiogroup">
                <button v-for="o in item.options" :key="o.value" type="button" role="radio" :aria-checked="modelValue === o.value"
                    @click="emit('update:modelValue', o.value)"
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    :class="modelValue === o.value ? 'bg-white text-primary-800 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-main'">
                    {{ o.label }}
                </button>
            </div>

            <!-- toggle -->
            <button v-else type="button" role="switch" :aria-checked="modelValue === true" :aria-label="item.label"
                @click="emit('update:modelValue', !modelValue)"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                :class="modelValue ? 'bg-primary-600' : 'bg-gray-300'">
                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform"
                    :class="modelValue ? 'translate-x-5' : 'translate-x-0.5'" />
            </button>
        </div>

        <p v-if="item.hint" class="mt-1.5 text-xs text-gray-400">{{ item.hint }}</p>
    </div>
</template>
