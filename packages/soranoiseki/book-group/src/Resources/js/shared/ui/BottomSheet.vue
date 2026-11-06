<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue';

// Modal that rises from the bottom on phones and is a centred card on larger screens.
// Locks page scrolling while open and closes on Escape or a tap on the backdrop.
const props = defineProps<{ open: boolean; title?: string }>();
const emit = defineEmits<{ (e: 'close'): void }>();

function onKey(e: KeyboardEvent): void {
    if (e.key === 'Escape') emit('close');
}

watch(() => props.open, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) window.addEventListener('keydown', onKey);
    else window.removeEventListener('keydown', onKey);
}, { immediate: true });

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50"
                @click.self="emit('close')">
                <div role="dialog" aria-modal="true" :aria-label="title"
                    class="w-full sm:max-w-lg max-h-[92dvh] flex flex-col rounded-t-3xl sm:rounded-3xl bg-white dark:bg-gray-800 text-main dark:text-gray-100 shadow-2xl">
                    <div class="sm:hidden pt-2 pb-1 flex justify-center" aria-hidden="true">
                        <span class="w-10 h-1 rounded-full bg-gray-300"></span>
                    </div>
                    <header v-if="title" class="flex items-center justify-between gap-3 px-5 pt-3 pb-2">
                        <h2 class="text-lg font-bold">{{ title }}</h2>
                        <button type="button" @click="emit('close')" aria-label="关闭"
                            class="w-9 h-9 -mr-2 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                        </button>
                    </header>
                    <div class="overflow-y-auto overscroll-contain px-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
