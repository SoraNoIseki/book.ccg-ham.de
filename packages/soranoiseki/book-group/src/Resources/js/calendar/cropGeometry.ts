import { computed, onBeforeUnmount, ref, watch, type Ref } from "vue";

/** Which part of a photo shows in the header: centre as fractions of the photo (0..1) and a zoom of 1..MAX_ZOOM. */
export interface Crop {
    x: number;
    y: number;
    zoom: number;
}

export const DEFAULT_CROP: Crop = { x: 0.5, y: 0.5, zoom: 1 };
export const MAX_ZOOM = 3;

const clamp = (v: number, min: number, max: number): number => Math.min(Math.max(v, min), Math.max(min, max));

/** Width in px of an element, kept up to date while it is resized (0 until it exists). */
export function useElementWidth(el: Ref<HTMLElement | null>): Ref<number> {
    const width = ref(0);
    let observer: ResizeObserver | null = null;

    watch(
        el,
        (node) => {
            observer?.disconnect();
            observer = null;
            if (!node) return;
            width.value = node.clientWidth;
            observer = new ResizeObserver(() => (width.value = node.clientWidth));
            observer.observe(node);
        },
        { immediate: true, flush: "post" },
    );
    onBeforeUnmount(() => observer?.disconnect());

    return width;
}

/**
 * Where a photo sits in a frame of `frameW` px wide and `ratio` (height / width) high.
 * Same arithmetic as crop_box() in storage/scripts/calendar/calendar_pdf.py: start from "cover", shrink by the zoom,
 * centre on (x, y) and keep inside the photo. Lengths named source* are in pixels of the photo itself.
 */
export function cropGeometry(frameW: Ref<number>, ratio: Ref<number>, nat: Ref<{ w: number; h: number }>, crop: Ref<Crop>) {
    const ready = computed(() => nat.value.w > 0 && nat.value.h > 0 && frameW.value > 0);
    const frameH = computed(() => frameW.value * ratio.value);
    /** Screen pixels per photo pixel */
    const scale = computed(() => {
        if (!ready.value) return 1;
        const cover = Math.max(frameW.value / nat.value.w, frameH.value / nat.value.h);
        return cover * crop.value.zoom;
    });
    /** Size of the visible part, in photo pixels */
    const sourceW = computed(() => frameW.value / scale.value);
    const sourceH = computed(() => frameH.value / scale.value);
    /** Top left corner of the visible part, in photo pixels */
    const left = computed(() => (ready.value ? clamp(crop.value.x * nat.value.w - sourceW.value / 2, 0, nat.value.w - sourceW.value) : 0));
    const top = computed(() => (ready.value ? clamp(crop.value.y * nat.value.h - sourceH.value / 2, 0, nat.value.h - sourceH.value) : 0));
    /** The centre that is really shown (the stored one can lie outside after zooming out) */
    const centre = computed(() => ({
        x: ready.value ? (left.value + sourceW.value / 2) / nat.value.w : crop.value.x,
        y: ready.value ? (top.value + sourceH.value / 2) / nat.value.h : crop.value.y,
    }));
    /** Style for an <img> that is absolutely positioned at the top left of the frame */
    const imgStyle = computed(() =>
        ready.value
            ? {
                  width: `${nat.value.w * scale.value}px`,
                  height: `${nat.value.h * scale.value}px`,
                  transform: `translate(${-left.value * scale.value}px, ${-top.value * scale.value}px)`,
              }
            : { width: "100%", height: "100%", objectFit: "cover" as const },
    );

    return { ready, frameH, scale, sourceW, sourceH, left, top, centre, imgStyle };
}

export { clamp };
