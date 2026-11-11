<script setup lang="ts">
import { computed, useSlots } from 'vue';
import { usePowerpointStore } from '../../store/powerpoint';
import type { TabId } from '../../types';

// A labelled textarea bound to store.content[tab][item].
// Slots: "toolbar" (above the textarea), "hint" (right-hand column).
const props = withDefaults(defineProps<{
    tab: TabId;
    item: string;
    label: string;
    rows?: number;
    placeholder?: string;
    disabled?: boolean;
}>(), { rows: 10 });

const ppt = usePowerpointStore();
const slots = useSlots();
const id = computed(() => `${props.tab}-${props.item}`);

const value = computed({
    get: () => ppt.content[props.tab]?.[props.item] ?? '',
    set: (v: string) => ppt.setField(props.tab, props.item, v),
});
</script>

<template>
    <div class="mb-8">
        <label :for="id" class="mb-2 block text-sm font-semibold text-main dark:text-light">{{ label }}</label>

        <!-- Toolbar sits above the grid so the textarea and the hints start at the same height -->
        <slot name="toolbar" />

        <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div class="col-span-3">
                <textarea :id="id" v-model="value" :rows="rows" :placeholder="placeholder ?? label"
                    :disabled="disabled"
                    class="w-full resize-y rounded-xl border border-gray-200 bg-gray-50/60 dark:bg-gray-900 py-3 px-4 text-base text-main dark:text-light placeholder:text-gray-400 outline-none transition focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 disabled:bg-gray-100 dark:disabled:bg-gray-800" />
            </div>
            <div v-if="slots.hint" class="space-y-5">
                <slot name="hint" />
            </div>
        </div>
    </div>
</template>
