<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useCalendarStore } from '../store/calendar';
import SearchSelect from '../../ppt/components/ui/SearchSelect.vue';

// Which design this calendar renders with (used for the PDF). History versions cannot change it.
const cal = useCalendarStore();
const { designs, calendarDesignId, editable, year, isImporting } = storeToRefs(cal);

const options = computed(() => designs.value.map((d) => ({ value: String(d.id), label: d.name })));
const current = computed(() => designs.value.find((d) => d.id === calendarDesignId.value));

function change(value: string): void {
    const id = Number(value);
    if (id && id !== calendarDesignId.value) cal.useDesign(id);
}
</script>

<template>
    <section class="mb-6 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-main dark:text-gray-100">{{ year }} 年日历使用的外观</h3>
                <p class="mt-0.5 text-xs text-gray-500">
                    <template v-if="editable">生成 PDF 时使用这一套外观。</template>
                    <template v-else>历史版本不能更换外观，但可以在下面选择任意外观进行预览。</template>
                </p>
            </div>

            <div class="w-full sm:w-72">
                <SearchSelect v-if="editable" :model-value="calendarDesignId !== null ? String(calendarDesignId) : ''" :options="options"
                    :disabled="isImporting" placeholder="选择外观..." @change="change" />
                <div v-else class="h-10 px-3 inline-flex items-center rounded-lg bg-gray-100 text-sm text-gray-600">
                    {{ current?.name ?? '未设置' }}
                </div>
            </div>
        </div>
    </section>
</template>
