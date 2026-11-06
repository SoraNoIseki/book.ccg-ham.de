<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';

// The designs that exist. Click one to look at it (and, with the design role, edit it).
// Creating, renaming and deleting are only offered to users who may edit designs.
const cal = useCalendarStore();
const { designs, designId, designName, calendarDesignId, canDesign, designDirty, isLoadingDesign } = storeToRefs(cal);

async function select(id: number): Promise<void> {
    if (id === designId.value) return;
    if (designDirty.value && !confirm('当前外观有尚未保存的修改，切换后这些修改会丢失。要继续吗？')) return;
    await cal.loadDesign(id);
}

// ---- inline name form, used both to create and to rename ----
const mode = ref<'create' | 'rename' | null>(null);
const name = ref('');
const input = ref<HTMLInputElement | null>(null);
const busy = ref(false);

async function open(m: 'create' | 'rename'): Promise<void> {
    mode.value = m;
    name.value = m === 'rename' ? designName.value : `${designName.value} 副本`;
    await nextTick();
    input.value?.focus();
    input.value?.select();
}

async function submit(): Promise<void> {
    const value = name.value.trim();
    if (!value || busy.value) return;
    busy.value = true;
    const ok = mode.value === 'create' ? await cal.createDesign(value) : await cal.renameDesign(value);
    busy.value = false;
    if (ok) mode.value = null;
}

async function remove(): Promise<void> {
    if (confirm(`确定要删除外观「${designName.value}」吗？\n它的修改记录仍会保留，但不能再被选用。`)) {
        await cal.deleteDesign();
    }
}

function usedBy(years: number[]): string {
    return years.length ? `用于 ${years.join('、')} 年` : '未被任何日历使用';
}
</script>

<template>
    <section class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <h3 class="text-sm font-bold text-main dark:text-gray-100">外观方案</h3>
            <button v-if="canDesign && mode === null" type="button" @click="open('create')"
                class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl text-sm font-semibold text-primary-800 bg-white ring-1 ring-primary-600/40 hover:bg-primary-100/60 transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                新建外观
            </button>
        </div>

        <!-- Designs -->
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="外观方案">
            <button v-for="d in designs" :key="d.id" type="button" role="tab" :aria-selected="d.id === designId" :disabled="isLoadingDesign"
                :title="usedBy(d.used_by)" @click="select(d.id)"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-semibold transition outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-60"
                :class="d.id === designId
                    ? 'bg-primary-600 text-white shadow-sm'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100'">
                {{ d.name }}
                <span v-if="d.id === calendarDesignId" class="px-1.5 py-0.5 rounded-full text-[11px] leading-none font-bold"
                    :class="d.id === designId ? 'bg-white/25 text-white' : 'bg-green-100 text-green-800'">使用中</span>
            </button>
        </div>

        <!-- Name form -->
        <form v-if="mode" class="mt-3 flex flex-wrap items-center gap-2" @submit.prevent="submit">
            <label class="sr-only" for="design-name">外观名称</label>
            <input id="design-name" ref="input" v-model="name" type="text" maxlength="100" autocomplete="off"
                :placeholder="mode === 'create' ? '新外观的名称' : '外观名称'"
                class="h-10 w-64 px-3 rounded-lg border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500"
                @keydown.esc="mode = null" />
            <button type="submit" :disabled="!name.trim() || busy"
                class="h-10 px-4 rounded-lg text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 transition">
                {{ mode === 'create' ? '创建' : '保存名称' }}
            </button>
            <button type="button" class="h-10 px-3 rounded-lg text-sm text-gray-500 hover:text-gray-800" @click="mode = null">取消</button>
            <span v-if="mode === 'create'" class="text-xs text-gray-400">会以当前选中的「{{ designName }}」为基础复制一份。</span>
        </form>

        <!-- Actions on the selected design -->
        <div v-if="canDesign && mode === null && designId" class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
            <span class="text-gray-500">当前选中：<b class="text-gray-700">{{ designName }}</b></span>
            <button type="button" class="font-medium text-primary-700 hover:underline" @click="open('rename')">重命名</button>
            <button type="button" class="font-medium text-gray-500 hover:text-red-600" @click="remove">删除</button>
        </div>
    </section>
</template>
