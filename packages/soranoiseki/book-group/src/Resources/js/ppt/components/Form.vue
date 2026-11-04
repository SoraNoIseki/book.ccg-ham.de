<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePowerpointStore } from '@book/ppt/store/powerpoint';

import TabPray from './tabs/TabPray.vue';
import TabScripture from './tabs/TabScripture.vue';
import TabPreach from './tabs/TabPreach.vue';
import TabSong from './tabs/TabSong.vue';
import TabReport from './tabs/TabReport.vue';
import TabWorker from './tabs/TabWorker.vue';

const ppt = usePowerpointStore();
const { activeTab } = storeToRefs(ppt);

</script>

<template>
    <!-- Tab panels -->
    <div v-show="activeTab === 'pray'">
        <TabPray />
    </div>
    <div v-show="activeTab === 'scripture'">
        <TabScripture />
    </div>
    <div v-show="activeTab === 'preach'">
        <TabPreach />
    </div>
    <div v-show="activeTab === 'song'">
        <TabSong />
    </div>
    <div v-show="activeTab === 'report'">
        <TabReport />
    </div>
    <div v-show="activeTab === 'worker'">
        <TabWorker />
    </div>
</template>
