<script setup lang="ts">
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';
</script>

<template>
    <ContentField tab="preach" item="item0" label="讲道标题" :rows="5">
        <template #hint>
            <AlertBox title="格式范例：">
                <li>善仆与恶仆</li>
                <li>吴振忠牧师证道</li>
                <li>路加福音 19:11-28</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>第一行为讲道标题，之后几行均为副标题</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="preach" item="item1" label="讲道（引言）">
        <template #hint>
            <AlertBox title="格式范例：">
                <li>1. 引言1</li>
                <li>2. 引言2</li>
                <li>3. ...</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>编号数字后请输入空格</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="preach" item="item2" label="讲道（经文理解与应用）">
        <template #hint>
            <AlertBox title="格式范例：">
                <li>1. 大纲1</li>
                <li>2. 大纲2</li>
                <li>3. ...</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>编号数字后请输入空格</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="preach" item="item3" label="讲道（总结）">
        <template #hint>
            <AlertBox type="danger" title="注意事项：">
                <li>可输入多行，但一般情况下不需要编号</li>
            </AlertBox>
        </template>
    </ContentField>
</template>
