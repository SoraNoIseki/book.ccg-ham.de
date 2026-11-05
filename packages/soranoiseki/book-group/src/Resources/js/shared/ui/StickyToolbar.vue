<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Toolbar pinned flush to the viewport top. Once pinned it shrinks smoothly; the slot receives
// `stuck` so the content can switch to its compact look.
//
// Anti-jitter: the sticky wrapper keeps a constant layout height (the expanded height), and only
// the inner bar shrinks visually. If the layout height changed, the content below would shift,
// the browser's scroll anchoring would nudge scrollTop, the sentinel would re-enter the viewport
// and the bar would toggle back and forth.
const sentinel = ref<HTMLElement | null>(null);
const bar = ref<HTMLElement | null>(null);
const stuck = ref(false);
const reservedHeight = ref(0);

let observer: IntersectionObserver | null = null;
let resizeObserver: ResizeObserver | null = null;
let settleTimer: number | undefined;
let settling = false;

// Only measure the expanded bar while it is not stuck and not mid-transition.
function measure(): void {
    if (stuck.value || settling || !bar.value) return;
    reservedHeight.value = bar.value.offsetHeight;
}

watch(stuck, () => {
    settling = true;
    window.clearTimeout(settleTimer);
    settleTimer = window.setTimeout(() => {
        settling = false;
        measure();
    }, 350);
});

onMounted(() => {
    if (sentinel.value && 'IntersectionObserver' in window) {
        observer = new IntersectionObserver(
            ([entry]) => (stuck.value = !entry.isIntersecting && entry.boundingClientRect.top < 0),
            { threshold: 0 },
        );
        observer.observe(sentinel.value);
    }

    if (bar.value && 'ResizeObserver' in window) {
        resizeObserver = new ResizeObserver(measure);
        resizeObserver.observe(bar.value);
    }
    measure();
});

onBeforeUnmount(() => {
    observer?.disconnect();
    resizeObserver?.disconnect();
    window.clearTimeout(settleTimer);
});
</script>

<template>
    <!-- Sentinel: leaving the viewport means the toolbar is pinned to the top -->
    <div ref="sentinel" class="h-px -mb-px" aria-hidden="true"></div>

    <!-- Sticky wrapper: constant layout height, click-through; the bar inside shrinks smoothly -->
    <div class="sticky top-0 z-30 mb-6 pointer-events-none"
        :style="reservedHeight ? { height: reservedHeight + 'px' } : undefined">
        <div ref="bar"
            class="pointer-events-auto backdrop-blur ring-1 ring-gray-200/70 dark:ring-gray-700 transition-all duration-300 ease-out"
            :class="stuck
                ? 'rounded-b-2xl rounded-t-none bg-white/95 dark:bg-gray-800/95 shadow-lg px-4 py-2 sm:px-5'
                : 'rounded-2xl bg-white/90 dark:bg-gray-800/90 shadow-sm p-4 sm:p-5'">
            <slot :stuck="stuck" />
        </div>
    </div>
</template>
