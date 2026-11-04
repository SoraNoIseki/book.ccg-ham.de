<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../../store/powerpoint';
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';
import SearchSelect from '../ui/SearchSelect.vue';

interface SongOption { id: string; name: string; album?: string; checked?: boolean }

const ppt = usePowerpointStore();
const { endpoints, date } = storeToRefs(ppt);

const songs = ref<SongOption[]>([]);
const loadingSong = ref(false);

// Selected song id per textarea (only drives the dropdown display).
const selected = reactive<Record<string, string>>({});

// Searchable options: label contains name, album and checked mark.
const options = computed<SelectOption[]>(() =>
    songs.value.map((song) => ({ value: song.id, label: optionLabel(song) })),
);

// A different version has different content, so forget the old selections.
watch(date, () => {
    for (const k of Object.keys(selected)) delete selected[k];
});

const items = [
    { item: 'item1', label: '诗歌1' },
    { item: 'item2', label: '诗歌2' },
    { item: 'item3', label: '诗歌3' },
    { item: 'item4', label: '回应诗歌' },
];

onMounted(async () => {
    try {
        const { data } = await axios.get(endpoints.value.songs);
        songs.value = data?.data ?? [];
    } catch {
        ppt.errors = ['无法加载歌库。'];
    }
});

function optionLabel(s: SongOption): string {
    const album = s.album && s.album.toLowerCase() !== 'na' ? ` | ${s.album}` : '';
    return `${s.name}${album}${s.checked ? ' ✅' : ''}`;
}

// Load the PPT script of the chosen song into the textarea; empty choice clears it.
async function onSelect(item: string, id: string): Promise<void> {
    if (!id) {
        ppt.setField('song', item, '');
        return;
    }
    loadingSong.value = true;
    try {
        const { data } = await axios.get(endpoints.value.song, { params: { id } });
        if (data?.success) ppt.setField('song', item, data.data?.script_text_for_ppt_worker || '');
    } catch {
        ppt.errors = ['无法加载诗歌内容。'];
    } finally {
        loadingSong.value = false;
    }
}

// Normalise punctuation in lyrics (everything after title + source line).
function beautify(item: string): void {
    const text = ppt.content.song?.[item] ?? '';
    const lines = text.split('\n').map((raw, i) => {
        let line = raw.trim();
        if (i > 1) {
            line = line
                .replace(/[。；，：“”（）、？！ ,.;:?!]/g, '　')
                .replace(/　+$/, '')
                .replace(/　+/g, '　');
        }
        return line;
    });
    ppt.setField('song', item, lines.join('\n'));
}
</script>

<template>
    <div class="mb-5">
        <AlertBox type="tip" title="提示">
            请再次核对，并根据司会的要求修改歌词顺序。
        </AlertBox>
    </div>

    <ContentField v-for="(s, idx) in items" :key="s.item" tab="song" :item="s.item" :label="s.label" :rows="15">
        <template #toolbar>
            <div class="w-full flex gap-4">
                <div class="w-full max-w-[500px] mb-3">
                    <SearchSelect v-model="selected[s.item]" :options="options" :disabled="loadingSong"
                        placeholder="从歌库中选择，可输入关键字搜索..." @change="(id: string) => onSelect(s.item, id)" />
                </div>
                <button type="button" @click="beautify(s.item)"
                    class="group relative overflow-hidden inline-flex items-center gap-1.5 shrink-0 h-[42px] px-4 mb-3 rounded-lg text-sm font-semibold text-white whitespace-nowrap bg-gradient-to-r from-primary-500 to-primary-700 shadow-md shadow-primary-700/30 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary-700/40 active:translate-y-0 active:scale-95 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 outline-none motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                    <!-- shine sweep on hover -->
                    <span aria-hidden="true"
                        class="pointer-events-none absolute inset-y-0 -left-full w-1/2 skew-x-[-20deg] bg-white/40 transition-transform duration-700 group-hover:translate-x-[400%] motion-reduce:hidden"></span>
                    <svg class="relative w-4 h-4 transition-transform duration-300 group-hover:rotate-12 group-hover:scale-110 motion-reduce:transition-none"
                        viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2l1.8 5.2L19 9l-5.2 1.8L12 16l-1.8-5.2L5 9l5.2-1.8L12 2zm7 11l.9 2.6L22.5 16.5l-2.6.9L19 20l-.9-2.6-2.6-.9 2.6-.9L19 13zM5 14l.9 2.6 2.6.9-2.6.9L5 21l-.9-2.6L1.5 17.5l2.6-.9L5 14z" />
                    </svg>
                    <span class="relative">自动优化格式</span>
                </button>
            </div>
        </template>
        <template v-if="idx === 0" #hint>
            <AlertBox title="格式范例：">
                <li>住在你里面</li>
                <li>赞美之泉《相信有爱就有奇迹》</li>
                <li>&nbsp;</li>
                <li>在干旱无水之地我渴慕祢</li>
                <li>在旷野无人之处我寻求祢</li>
                <li>...</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>前两行为<span class="font-semibold mx-1">标题</span>和<span class="font-semibold mx-1">出处</span></li>
                <li>诗歌段落用<span class="font-semibold mx-1">空行</span>分隔；如果没有分隔则自动按照每五行一页自动分割</li>
            </AlertBox>
        </template>
    </ContentField>
</template>
