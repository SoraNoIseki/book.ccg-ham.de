<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import Sortable from 'sortablejs';
import { usePowerpointStore } from '../../store/powerpoint';
import AlertBox from '../ui/AlertBox.vue';

const ppt = usePowerpointStore();
const listEl = ref<HTMLElement | null>(null);
let sortable: Sortable | null = null;

// Store keeps item1..itemN; the UI works on an ordered array (at least one row).
const items = computed<string[]>(() => {
    const map = ppt.content.report ?? {};
    const keys = Object.keys(map).sort(
        (a, b) => Number(a.replace('item', '')) - Number(b.replace('item', '')),
    );
    const values = keys.map((k) => map[k]);
    return values.length ? values : [''];
});

function update(index: number, value: string): void {
    const next = [...items.value];
    next[index] = value;
    ppt.setTab('report', next);
}

function add(index: number): void {
    const next = [...items.value];
    next.splice(index + 1, 0, '');
    ppt.setTab('report', next);
}

function remove(index: number): void {
    if (!confirm('确认要删除吗？')) return;
    const next = items.value.filter((_, i) => i !== index);
    ppt.setTab('report', next.length ? next : ['']);
}

onMounted(() => {
    if (!listEl.value) return;
    sortable = Sortable.create(listEl.value, {
        handle: '.move',
        animation: 150,
        // Revert the DOM move and let Vue re-render from the reordered data.
        onEnd: async (evt) => {
            const { oldIndex, newIndex } = evt;
            if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) return;
            evt.item.remove();
            evt.from.insertBefore(evt.item, evt.from.children[oldIndex] ?? null);
            const next = [...items.value];
            const [moved] = next.splice(oldIndex, 1);
            next.splice(newIndex, 0, moved);
            await nextTick();
            ppt.setTab('report', next);
        },
    });
});

onBeforeUnmount(() => sortable?.destroy());
</script>

<template>
    <div class="mb-5">
        <label class="mb-3 block text-base font-medium text-main dark:text-light">报告</label>

        <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div ref="listEl" class="col-span-3">
                <div v-for="(text, i) in items" :key="i" class="flex mb-3">
                    <textarea rows="3" :value="text" placeholder="请填写报告事项..."
                        @input="update(i, ($event.target as HTMLTextAreaElement).value)"
                        class="w-full resize-none rounded-md border border-gray-200 bg-white dark:bg-gray-900 py-3 px-6 text-base font-medium text-main dark:text-light outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md" />
                    <div class="ml-3 flex flex-col justify-between text-gray-500">
                        <button type="button" title="拖动排序" class="move cursor-move hover:text-primary-700 dark:hover:text-primary-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 16H4l6 6V2H8zm6-11v17h2V8h4l-6-6z" /></svg>
                        </button>
                        <button type="button" title="删除" @click="remove(i)" class="hover:text-red-600">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4c-4.419 0-8 3.582-8 8s3.581 8 8 8 8-3.582 8-8-3.581-8-8-8zm3.707 10.293c.391.391.391 1.023 0 1.414-.195.195-.451.293-.707.293s-.512-.098-.707-.293l-2.293-2.293-2.293 2.293c-.195.195-.451.293-.707.293s-.512-.098-.707-.293c-.391-.391-.391-1.023 0-1.414l2.293-2.293-2.293-2.293c-.391-.391-.391-1.023 0-1.414s1.023-.391 1.414 0l2.293 2.293 2.293-2.293c.391-.391 1.023-.391 1.414 0s.391 1.023 0 1.414l-2.293 2.293 2.293 2.293z" /></svg>
                        </button>
                        <button type="button" title="在下方添加" @click="add(i)" class="hover:text-primary-700 dark:hover:text-primary-300">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14" /></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <AlertBox title="格式范例：（无需编号）">
                    <li>每个输入框为一页报告事项</li>
                    <li>可以换行</li>
                </AlertBox>
            </div>
        </div>
    </div>
</template>
