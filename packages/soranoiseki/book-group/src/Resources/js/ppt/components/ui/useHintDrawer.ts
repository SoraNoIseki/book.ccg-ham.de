import { onBeforeUnmount, onMounted, ref } from 'vue';

// Shared state for the format-help panels: only one is open at a time.
// The panel is a drawer: from the right on wide screens, a bottom sheet on narrow screens.
export const activeHint = ref<string | null>(null);

let listeners = 0;
const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') activeHint.value = null; };

export function useHintDrawer() {
    onMounted(() => {
        if (listeners++ === 0) window.addEventListener('keydown', onKey);
    });
    onBeforeUnmount(() => {
        if (--listeners === 0) window.removeEventListener('keydown', onKey);
    });
    return { activeHint };
}
