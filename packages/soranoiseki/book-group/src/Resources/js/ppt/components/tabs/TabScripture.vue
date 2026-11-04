<script setup lang="ts">
import { usePowerpointStore } from '../../store/powerpoint';
import BibleSelector from '../BibleSelector.vue';
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';

const ppt = usePowerpointStore();

// Selecting a book fills the textarea with "<book>:1[*]" (whole chapter 1 as a starting point).
function applyBook(item: 'item1' | 'item3', book: string): void {
    ppt.setField('scripture', item, `${book}:1[*]`);
}
</script>

<template>
    <ContentField tab="scripture" item="item1" label="经文（宣召）">
        <template #toolbar>
            <BibleSelector @select="(b) => applyBook('item1', b)" />
        </template>
        <template #hint>
            <AlertBox title="格式范例1：">
                <li>诗篇:7[*]</li>
                <li>诗篇:7[1-5]</li>
                <li>诗篇:7[1-5,8-9]</li>
            </AlertBox>
            <AlertBox title="格式范例2：">
                <li>1 第一节经文</li>
                <li>2 第二节经文</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>方括号及其中所有字符均<span class="font-semibold mx-1">必须</span>为英文字符</li>
                <li>经文编号后请输入空格</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="scripture" item="item2" label="经文（启应经文）">
        <template #hint>
            <AlertBox title="格式范例：（无需编号）">
                <li>承认过犯</li>
                <li>经文（启）</li>
                <li>经文（应）</li>
                <li>...</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="scripture" item="item3" label="经文（读经）">
        <template #toolbar>
            <BibleSelector @select="(b) => applyBook('item3', b)" />
        </template>
        <template #hint>
            <AlertBox title="格式范例：">
                <li>格式同宣召</li>
            </AlertBox>
        </template>
    </ContentField>
</template>
