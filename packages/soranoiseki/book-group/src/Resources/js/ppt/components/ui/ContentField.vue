<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useSlots, watch } from 'vue';
import { usePowerpointStore } from '../../store/powerpoint';
import type { TabId } from '../../types';
import HintToggle from './HintToggle.vue';
import { useHintDrawer } from './useHintDrawer';

// A labelled textarea bound to store.content[tab][item].
// Slots: "toolbar" (above the textarea), "hint" (format help: drawer from the right on wide screens,
// bottom sheet of 80% screen height on narrow screens).
// Prop "tip": one-line key format reminder that is always visible under the textarea.
const props = withDefaults(defineProps<{
    tab: TabId;
    item: string;
    label: string;
    rows?: number;
    placeholder?: string;
    disabled?: boolean;
    tip?: string;
    fill?: boolean;
}>(), { rows: 10 });

const ppt = usePowerpointStore();
const slots = useSlots();
const { activeHint } = useHintDrawer();

const id = computed(() => `${props.tab}-${props.item}`);
const hintId = computed(() => `${id.value}-hint`);
const open = computed(() => activeHint.value === id.value);

function toggle(): void {
    activeHint.value = open.value ? null : id.value;
}

// The drawer belongs to the visible tab only.
watch(() => ppt.activeTab, () => { if (open.value) activeHint.value = null; });
onBeforeUnmount(() => { if (open.value) activeHint.value = null; });

// "fill": the textarea starts a little below the title and stretches down to the bottom of the viewport.
const area = ref<HTMLTextAreaElement | null>(null);
const fillHeight = ref<number | null>(null);

function measure(): void {
    if (!props.fill || !area.value) return;
    const top = area.value.getBoundingClientRect().top;
    const reserve = props.tip ? 56 : 24; // room for the tip line and the page bottom margin
    fillHeight.value = Math.max(256, Math.round(window.innerHeight - top - reserve));
}

onMounted(() => {
    if (!props.fill) return;
    nextTick(measure);
    window.addEventListener('resize', measure);
});
onBeforeUnmount(() => window.removeEventListener('resize', measure));

const value = computed({
    get: () => ppt.content[props.tab]?.[props.item] ?? '',
    set: (v: string) => ppt.setField(props.tab, props.item, v),
});
</script>

<template>
    <div class="mb-8">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
            <label :for="id" class="block text-sm font-semibold text-main dark:text-light">{{ label }}</label>
            <HintToggle v-if="slots.hint" :open="open" :controls="hintId" @toggle="toggle" />
        </div>

        <slot name="toolbar" />

        <textarea :id="id" ref="area" v-model="value" :rows="rows" :placeholder="placeholder ?? label" :disabled="disabled"
            :style="fill && fillHeight ? { height: fillHeight + 'px' } : undefined"
            :class="fill ? 'mt-2 align-top leading-snug' : ''"
            class="w-full resize-y rounded-xl border border-gray-200 bg-gray-50/60 dark:bg-gray-900 py-3 px-4 text-base text-main dark:text-light placeholder:text-gray-400 outline-none transition focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 disabled:bg-gray-100 dark:disabled:bg-gray-800" />

        <p v-if="tip" class="mt-2 flex items-start gap-1.5 text-sm text-primary-800 dark:text-primary-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 15h-2v-6h2v6Zm0-8h-2V7h2v2Z" />
            </svg>
            <span>{{ tip }}</span>
        </p>

        <Teleport to="body">
            <!-- Backdrop: only for the bottom sheet on narrow screens -->
            <Transition enter-active-class="transition-opacity duration-300" leave-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0" leave-to-class="opacity-0">
                <div v-if="slots.hint && open" class="fixed inset-0 z-40 bg-black/30 lg:hidden" aria-hidden="true"
                    @click="activeHint = null" />
            </Transition>

            <!-- Bottom sheet (narrow) / right drawer (wide) -->
            <Transition enter-active-class="transition-transform duration-300 ease-out"
                leave-active-class="transition-transform duration-200 ease-in"
                enter-from-class="translate-y-full lg:translate-y-0 lg:translate-x-full"
                leave-to-class="translate-y-full lg:translate-y-0 lg:translate-x-full">
                <aside v-if="slots.hint && open" :id="hintId" role="complementary" :aria-label="`${label} 格式说明`"
                    class="fixed z-50 flex flex-col bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700 inset-x-0 bottom-0 h-[80dvh] rounded-t-2xl lg:inset-x-auto lg:inset-y-0 lg:right-0 lg:h-auto lg:w-[22rem] lg:rounded-none">
                    <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-gray-300 dark:bg-gray-600 lg:hidden" aria-hidden="true"></div>
                    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <div class="min-w-0">
                            <div class="text-xs text-gray-500 dark:text-gray-400">格式说明</div>
                            <div class="truncate text-sm font-semibold text-main dark:text-light">{{ label }}</div>
                        </div>
                        <button type="button" aria-label="关闭格式说明" @click="activeHint = null"
                            class="shrink-0 rounded-md p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 outline-none focus-visible:ring-2 focus-visible:ring-primary-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" aria-hidden="true">
                                <path d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto p-4">
                        <slot name="hint" />
                    </div>
                </aside>
            </Transition>
        </Teleport>
    </div>
</template>
