<template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="text-main dark:text-gray-100">
                <div class="px-3 py-2 md:px-4 md:py-4 text-sm text-gray-700 font-semibold">
                    <p>提示：点击左边的折叠箭头可加载歌词。</p>
                </div>

                <div class="px-3 py-2 md:px-4 md:py-4 text-gray-700">
                    <input type="text" v-model="search" @input="onSearch" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring focus:ring-primary-500 focus:border-primary-500" placeholder="搜索歌曲名称或乐队名称">
                </div>

                <div class="px-3 py-2 md:px-4 md:py-4">
                    <div class="relative overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead
                                class="font-semibold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th colspan="3" class="px-2 py-2 md:px-3 md:py-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="w-full">共 {{ filteredSongs.length }} / {{ songs.length }} 首诗歌</div>
                                            <div class="w-full bg-red-500 rounded-md h-5 dark:bg-gray-700 relative">
                                                <div class="bg-green-600 h-5 rounded-md" :style="{ width: progress + '%' }">
                                                    <span class="absolute w-full text-center font-semibold text-white text-sm">校对进度 {{ progress }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(song, index) in filteredSongs" :key="index">    
                                    <tr class="bg-white border-b text-gray-700 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-2 py-2 md:px-3 md:py-4 cursor-pointer w-10" @click="toggleSongText(song)">
                                            <ChevronDownIcon class="w-4 h-4 text-gray-600"></ChevronDownIcon>
                                        </td>
                                        <td class="px-2 py-2 md:px-4 md:py-4">
                                            <span class="font-semibold mb-2 flex items-center">
                                                <span class="inline-block mr-2">{{ song.name }}</span>
                                                <span class="text-green-700 text-xs inline-block" v-if="song.checked">已校对</span>
                                            </span>
                                            <span v-if="song.band.toLowerCase() !== 'na'"> {{ song.band }}</span> 
                                            <span v-if="song.band.toLowerCase() !== 'na' && song.album.toLowerCase() !== 'na'"> | </span>
                                            <span v-if="song.album.toLowerCase() !== 'na'"> {{ song.album }}</span>
                                        </td>
                                        <td class="px-2 py-2 md:px-4 md:py-4">
                                            <a target="_blank" :href="searchInYoutube(song)" class="text-primary-600 hover:text-primary-900">
                                                YouTube
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-show="currentToggleSong?.group_id === song.group_id" class="border-b">
                                        <td class="px-2 py-2 md:px-3 md:py-4"></td>
                                        <td colspan="2" class="px-2 py-2 md:px-4 md:py-4 text-left">
                                            <div v-if="loading">
                                                <LoadingIcon class="w-6 h-6 text-gray-700"></LoadingIcon>
                                            </div>
                                            <div v-else>
                                                <pre class="text-sm text-gray-700 dark:text-gray-200" v-html="currentSongText?.text"></pre>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">

import { ref, Ref, onMounted } from 'vue';
import axios from 'axios';
import { LoadingIcon, ChevronDownIcon } from '../icons';

interface Song {
    group_id: string;
    name: string;
    band: string;
    album: string;
    checked: boolean;
}

interface SongText {
    group_id: string;
    text: string;
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

const songs: Ref<Song[]> = ref([]);
const filteredSongs: Ref<Song[]> = ref([]);
const loading: Ref<boolean> = ref(false);
const songTextArray: Ref<SongText[]> = ref([]);
const currentSongText: Ref<SongText | null> = ref(null);
const currentToggleSong: Ref<Song | null> = ref(null);
const search: Ref<string> = ref('');

let progress = 0;
let debounceTimeouts: any = null;

onMounted(async () => {
    const response = await Promise.resolve(axios.get<ApiResponse<Song[]>>('/api/songs'));
    songs.value = response.data.data;
    progress = parseFloat((songs.value.filter((song) => song.checked).length / songs.value.length * 100).toFixed(1));
    filterSongs();
});

const searchInYoutube = (song: Song) => {
    let query = `${song.name}`;
    if (song.band && song.band.toLowerCase() !== 'na') {
        query += `+${song.band}`;
    }
    return `https://www.youtube.com/results?search_query=${query}`;
}

const fetchSongText = async (song: Song): Promise<SongText | null> => {
    const response = await Promise.resolve(axios.get<ApiResponse<SongText>>(`/api/songs/${song.group_id}`));
    
    let songText = response.data.data[0];
    if (songText) {
        songTextArray.value.push(songText);
        return songText;
    }
    
    return null;
}

const toggleSongText = async (song: Song) => {
    if (currentSongText.value?.group_id === song.group_id) {
        currentSongText.value = null;
        currentToggleSong.value = null;
        return;
    }

    currentToggleSong.value = song;
    loading.value = true;

    let songText: SongText | undefined = undefined;
    if (songTextArray.value.find((songText) => songText.group_id === song.group_id)) {
        songText = songTextArray.value.find((songText) => songText.group_id === song.group_id);
    } else {
        songText = (await fetchSongText(song)) ?? undefined;
    }

    if (songText) {
        currentSongText.value = songText;
    }

    loading.value = false;
}

const onSearch = () => {
    clearTimeout(debounceTimeouts);
    debounceTimeouts = setTimeout(() => {
        filterSongs();
    }, 700);
}

const filterSongs = () => {
    if (search.value === '') {
        filteredSongs.value = songs.value;
    }

    filteredSongs.value = songs.value.filter((song) => {
        return song.name.toLowerCase().includes(search.value.toLowerCase()) || song.band.toLowerCase().includes(search.value.toLowerCase());
    });
}

</script>