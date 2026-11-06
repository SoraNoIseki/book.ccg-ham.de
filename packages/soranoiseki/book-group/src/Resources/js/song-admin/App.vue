<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import { extractErrors } from '../shared/http';
import TabBar, { type TabItem } from '../shared/ui/TabBar.vue';
import MessageList from '../shared/ui/MessageList.vue';
import SongTable from './components/SongTable.vue';
import UnsavedTable from './components/UnsavedTable.vue';
import SongEditor from './components/SongEditor.vue';
import type { SongRow, UnsavedRow } from './types';

const BASE = '/songs';

const songs = ref<SongRow[]>([]);
const unsaved = ref<UnsavedRow[]>([]);
const loading = ref(true);
const errors = ref<string[]>([]);
const success = ref<string | null>(null);

const tab = ref<'all' | 'unsaved'>('all');
// The editor is part of the page; /songs/{id} deep links open it directly.
const editingId = ref<string | null>(document.getElementById('app-song-admin')?.dataset.songId || null);
const highlightId = ref<string | null>(null);

const tabs = computed<TabItem[]>(() => [
    {
        id: 'all', label: '所有诗歌',
        icon: 'M9 18V5l12-2v13M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm12-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    },
    {
        id: 'unsaved', label: '未保存的诗歌',
        icon: 'M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2',
        status: unsaved.value.length ? 'partial' : 'done', badge: String(unsaved.value.length),
    },
]);

async function load(): Promise<void> {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/songs-admin');
        songs.value = data.songs;
        unsaved.value = data.unsaved;
    } catch (e) {
        errors.value = await extractErrors(e, '无法加载诗歌列表。');
    } finally {
        loading.value = false;
    }
}

function notify(message: string): void {
    errors.value = [];
    success.value = message;
}

function fail(messages: string[]): void {
    success.value = null;
    errors.value = messages;
}

// ---------------- navigation (history-aware, so the back button works) ----------------
function open(id: string): void {
    editingId.value = id;
    history.pushState({ song: id }, '', `${BASE}/${id}`);
    window.scrollTo({ top: 0 });
}

async function close(id?: string): Promise<void> {
    // Refresh the list: the song may have been renamed, checked or created
    const leaving = id ?? editingId.value;
    editingId.value = null;
    history.pushState({}, '', BASE);
    await load();
    highlightId.value = leaving;
    await nextTick();
    document.getElementById(`song-${leaving}`)?.scrollIntoView({ block: 'center' });
}

function onPopState(): void {
    const m = location.pathname.match(/\/songs\/([^/]+)$/);
    editingId.value = m ? m[1] : null;
    if (!m) load();
}

async function remove(song: SongRow): Promise<void> {
    if (!confirm(`确认要删除「${song.name}」吗？`)) return;
    try {
        const { data } = await axios.delete(`/api/songs-admin/${song.id}`);
        songs.value = songs.value.filter((s) => s.id !== song.id);
        notify(data.message || '已删除');
        await load(); // the content may become "unsaved" again
    } catch (e) {
        fail(await extractErrors(e, '删除失败。'));
    }
}

async function upload(row: UnsavedRow): Promise<void> {
    try {
        const { data } = await axios.post(`/api/songs-admin/contents/${row.id}/upload`);
        notify(data.message || '已上传');
        open(data.id);
    } catch (e) {
        fail(await extractErrors(e, '上传失败。'));
    }
}

onMounted(() => {
    load();
    window.addEventListener('popstate', onPopState);
});
onBeforeUnmount(() => window.removeEventListener('popstate', onPopState));
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div class="mb-6">
            <MessageList :errors="errors" :success="success" />
        </div>

        <SongEditor v-if="editingId" :key="editingId" :song-id="editingId" @back="close" @saved="notify"
            @error="fail" />

        <template v-else>
            <div class="mb-6">
                <TabBar :tabs="tabs" :model-value="tab" @update:model-value="tab = $event as 'all' | 'unsaved'" />
            </div>

            <div v-if="loading && !songs.length" class="py-16 text-center text-sm text-gray-400">加载中...</div>

            <SongTable v-else-if="tab === 'all'" :songs="songs" :highlight-id="highlightId" @open="open"
                @remove="remove" />
            <UnsavedTable v-else :rows="unsaved" @upload="upload" />
        </template>
    </div>
</template>
