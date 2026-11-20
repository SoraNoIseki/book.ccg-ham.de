<script setup lang="ts">
import { computed, ref } from 'vue';
import { cropGeometry, DEFAULT_CROP, useElementWidth, type Crop } from '../cropGeometry';

// A photo shown the way the header of the calendar shows it: in a frame of the header's proportions, with
// the chosen part visible. Without a crop it is centred, like "object-fit: cover".
const props = defineProps<{ src: string; ratio: number; crop?: Crop | null; alt?: string }>();

const frame = ref<HTMLElement | null>(null);
const frameW = useElementWidth(frame);
const nat = ref({ w: 0, h: 0 });
const g = cropGeometry(frameW, computed(() => props.ratio), nat, computed(() => props.crop ?? DEFAULT_CROP));

function onLoad(e: Event): void {
    const img = e.target as HTMLImageElement;
    nat.value = { w: img.naturalWidth, h: img.naturalHeight };
}
</script>

<template>
    <div ref="frame" class="relative w-full overflow-hidden" :style="{ aspectRatio: `1 / ${ratio}` }">
        <img :key="src" :src="src" :alt="alt ?? ''" loading="lazy" draggable="false" class="absolute left-0 top-0 max-w-none"
            :style="g.imgStyle.value" @load="onLoad" />
    </div>
</template>
