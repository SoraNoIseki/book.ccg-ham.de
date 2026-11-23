<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../../store/powerpoint';
import AnnouncementBar from '../AnnouncementBar.vue';

const ppt = usePowerpointStore();
const { date, includeVideo, forceHolyDinner, isHolyDinner, willHaveHolyDinner, defaultSongVersion } = storeToRefs(ppt);

// Radio value: the user's choice, otherwise the month default. Choosing stores an explicit value.
const songVersion = computed<'1' | '2'>({
    get: () => ppt.effectiveSongVersion,
    set: (v) => { ppt.songVersion = v; },
});

const sets = [
    { value: '1', label: '靠近十架' },
    { value: '2', label: '宝架清影' },
] as const;
</script>

<template>
    <div class="space-y-5">
        <AnnouncementBar />

        <p class="text-base text-main dark:text-light">
            主内平安！愿每一份用心的预备，都成为主日敬拜中的祝福。
        </p>

        <!-- Holy communion set -->
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="mb-1 text-sm font-semibold text-main dark:text-light">圣餐流程</h3>
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                只有每月第一周（1 至 7 号）才会自动加入圣餐流程。
            </p>
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-main dark:text-light">
                <label v-for="s in sets" :key="s.value" class="inline-flex items-center gap-2 cursor-pointer">
                    <input v-model="songVersion" type="radio" name="song-version" :value="s.value"
                        class="accent-primary-600" />
                    {{ s.label }}
                    <span v-if="defaultSongVersion === s.value"
                        class="rounded-full bg-primary-100 px-1.5 py-0.5 text-[11px] leading-none text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">本月默认</span>
                </label>
            </div>

            <!-- Force the communion flow outside of the first week, e.g. an extra communion in the middle of a month -->
            <div class="mt-4 flex items-center justify-between gap-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                <div>
                    <div class="text-sm font-medium text-main dark:text-light">额外的圣餐流程</div>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">月中设有额外的圣餐时可以开启此选项，不受“第一周”限制。</p>
                </div>
                <button type="button" role="switch" :aria-checked="forceHolyDinner" aria-label="额外的圣餐流程"
                    @click="forceHolyDinner = !forceHolyDinner"
                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2"
                    :class="forceHolyDinner ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200"
                        :class="forceHolyDinner ? 'translate-x-5' : 'translate-x-0.5'" />
                </button>
            </div>

            <p class="mt-3 text-xs" :class="willHaveHolyDinner ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <template v-if="isHolyDinner">{{ date }} 在第一周，将加入圣餐流程。</template>
                <template v-else-if="forceHolyDinner">{{ date }} 不在第一周，但已加入额外的圣餐流程。</template>
                <template v-else>{{ date }} 不在第一周，不会加入圣餐流程（所选版本不生效）。</template>
            </p>
        </section>

        <!-- Bell video -->
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-main dark:text-light">钟声视频</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">在开场页之后加入钟声视频页。</p>
                </div>
                <button type="button" role="switch" :aria-checked="includeVideo" aria-label="加入钟声视频"
                    @click="includeVideo = !includeVideo"
                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2"
                    :class="includeVideo ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200"
                        :class="includeVideo ? 'translate-x-5' : 'translate-x-0.5'" />
                </button>
            </div>
        </section>
    </div>
</template>
