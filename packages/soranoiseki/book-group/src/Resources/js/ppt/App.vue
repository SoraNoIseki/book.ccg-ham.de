<script setup lang="ts">
import { onMounted } from 'vue';
import { usePowerpointStore } from './store/powerpoint';
import VersionSelector from './components/VersionSelector.vue';
import MessageBar from './components/MessageBar.vue';
import Form from './components/Form.vue';

const ppt = usePowerpointStore();

// Initial load: version comes from ?v= (falls back to this week on the backend).
onMounted(() => ppt.fetchInit());
</script>

<template>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-main dark:text-gray-100">
                <VersionSelector />
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-main dark:text-gray-100">
                <MessageBar />
                <Form />
            </div>
        </div>
    </div>
</template>
