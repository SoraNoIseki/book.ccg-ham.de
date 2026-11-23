<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { usePowerpointStore } from '../../store/powerpoint';
import BibleSelector from '../BibleSelector.vue';
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';
import SearchSelect from '../ui/SearchSelect.vue';

interface ResponseScripture { key: string; label: string; title: string; text: string }

const ppt = usePowerpointStore();

// Responsive readings (启应经文) library shipped with ppt_worker.
const library = ref<ResponseScripture[]>([]);
const selectedLibrary = ref('');
const libraryOptions = computed<SelectOption[]>(() =>
    library.value.map((r) => ({ value: r.key, label: r.label })),
);

onMounted(async () => {
    try {
        const { data } = await axios.get(ppt.endpoints.responseScriptures);
        library.value = data?.data ?? [];
    } catch {
        ppt.errors = ['无法加载启应经文库。'];
    }
});

// Fill the textarea with the title (without number) on the first line, followed by the passage.
function applyLibrary(key: string): void {
    const item = library.value.find((r) => r.key === key);
    if (!item) return;
    ppt.setField('scripture', 'item2', `${item.title}\n${item.text}`);
}

// Selecting a book fills the textarea with "<book>:1[*]" (whole chapter 1 as a starting point).
function applyBook(item: 'item1' | 'item3', book: string): void {
    ppt.setField('scripture', item, `${book}:1[*]`);
}
</script>

<template>
    <ContentField tab="scripture" item="item1" label="经文（宣召）"
        tip="格式：书卷:章[节]，如 诗篇:46[1-3]；多段经文每行一段">
        <template #toolbar>
            <BibleSelector @select="(b) => applyBook('item1', b)" />
        </template>
        <template #hint>
            <AlertBox title="格式范例1：">
                <li>诗篇:7[*]</li>
                <li>诗篇:7[1-5]</li>
                <li>诗篇:7[1-5,8-9]</li>
            </AlertBox>
            <AlertBox title="多段经文：（每行一段）">
                <li>诗篇:46[1-3]</li>
                <li>罗马书:8[28-30]</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>方括号及其中所有字符均<span class="font-semibold mx-1">必须</span>为英文字符</li>
                <li>经文编号后请输入空格</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="scripture" item="item2" label="经文（启应经文）"
        tip="可从库中选择，或按章节输入（多段每行一段），或手工输入">
        <template #toolbar>
            <div class="w-full max-w-[500px] mb-3">
                <SearchSelect v-model="selectedLibrary" :options="libraryOptions"
                    placeholder="从启应经文库中选择，可输入关键字搜索..." @change="applyLibrary" />
            </div>
        </template>
        <template #hint>
            <AlertBox title="方式1：从库中选择">
                <li>选择后标题和经文会载入文本框，可继续修改</li>
            </AlertBox>
            <AlertBox title="方式2：按圣经章节输入（格式同宣召，多段每行一段）">
                <li>诗篇:46[1-3]</li>
                <li>罗马书:8[28-30]</li>
            </AlertBox>
            <AlertBox title="方式3：手工输入（无需编号）">
                <li>承认过犯</li>
                <li>经文（启）</li>
                <li>经文（应）</li>
                <li>...</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="scripture" item="item3" label="经文（读经）"
        tip="格式同宣召：书卷:章[节]，多段经文每行一段">
        <template #toolbar>
            <BibleSelector @select="(b) => applyBook('item3', b)" />
        </template>
        <template #hint>
            <AlertBox title="格式范例：">
                <li>格式同宣召，多段经文每行一段</li>
            </AlertBox>
        </template>
    </ContentField>
</template>
