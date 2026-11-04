<script setup lang="ts">
import ContentField from '../ui/ContentField.vue';
import AlertBox from '../ui/AlertBox.vue';
</script>

<template>
    <ContentField tab="pray" item="item1" label="代祷事项（汉堡教会）" :rows="15">
        <template #hint>
            <AlertBox title="格式范例：（无需编号）">
                <li>代祷事项1</li>
                <li>代祷事项2</li>
                <li>...</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="pray" item="item2" label="代祷事项（姐妹团契1）">
        <template #hint>
            <AlertBox title="格式范例：">
                <li>慕尼黑信义华人教会 (München)</li>
                <li>全年平均聚会人数：80人</li>
                <li>1. 代祷事项1</li>
                <li>2. 代祷事项2</li>
                <li>3. 代祷事项3</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>编号数字后请输入空格</li>
            </AlertBox>
        </template>
    </ContentField>

    <ContentField tab="pray" item="item3" label="代祷事项（姐妹团契2）">
        <template #hint>
            <AlertBox title="格式范例：">
                <li>慕尼黑信义华人教会 (München)</li>
                <li>全年平均聚会人数：80人</li>
                <li>1. 代祷事项1</li>
                <li>2. 代祷事项2</li>
                <li>3. 代祷事项3</li>
            </AlertBox>
            <AlertBox type="danger" title="注意事项：">
                <li>编号数字后请输入空格</li>
            </AlertBox>
        </template>
    </ContentField>
</template>
