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
    <div class="mb-5">
        <label :for="id" class="mb-3 block text-base font-medium text-main dark:text-light">{{ label }}</label>

        <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div class="col-span-3">
                <slot name="toolbar" />
                <textarea :id="id" v-model="value" :rows="rows" :placeholder="placeholder ?? label"
                    :disabled="disabled"
                    class="w-full resize-none rounded-md border border-gray-200 bg-white dark:bg-gray-900 py-3 px-6 text-base font-medium text-main dark:text-light outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md disabled:bg-gray-50" />
            </div>
            <div v-if="slots.hint" class="space-y-5">
                <slot name="hint" />
            </div>
        </div>
    </div>
</template>
