<script setup lang="ts">
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '../../store/powerpoint';
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';

interface SongOption { id: string; name: string; album?: string; checked?: boolean }

const ppt = usePowerpointStore();
const { endpoints } = storeToRefs(ppt);

const songs = ref<SongOption[]>([]);
const loadingSong = ref(false);

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
async function onSelect(item: string, e: Event): Promise<void> {
    const id = (e.target as HTMLSelectElement).value;
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
        <AlertBox title="关于歌词库">
            歌词库数据提取自2018年至今所有的PPT，可能存在错误，近期将开展校对工作。
        </AlertBox>
    </div>

    <ContentField v-for="(s, idx) in items" :key="s.item" tab="song" :item="s.item" :label="s.label" :rows="15">
        <template #toolbar>
            <div class="w-full flex gap-4">
                <select :disabled="loadingSong" @change="onSelect(s.item, $event)"
                    class="bg-gray-50 border border-gray-300 text-main text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full max-w-[500px] mb-3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">从歌库中选择...</option>
                    <option v-for="song in songs" :key="song.id" :value="song.id">{{ optionLabel(song) }}</option>
                </select>
                <button type="button" @click="beautify(s.item)"
                    class="text-sm w-32 px-3 py-2.5 mb-3 bg-blue-100 rounded-md font-semibold border border-gray-300">
                    自动优化格式
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
