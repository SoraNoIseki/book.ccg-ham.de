<script setup lang="ts">
import UiSingleSelect from '@book/task-plan/components/ui/UiSingleSelect.vue';

// Searchable single select used by the PPT tabs (songs, bible books).
// Wraps the planer's UiSingleSelect and overrides its look locally, because that component
// is shared with the planer and must stay untouched.
withDefaults(defineProps<{
    modelValue?: string;
    options: SelectOption[] | SelectOptionGroup[];
    groups?: boolean;
    placeholder?: string;
    disabled?: boolean;
}>(), { modelValue: '', groups: false, placeholder: '请选择，可输入关键字搜索...', disabled: false });

defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'change', value: string): void;
}>();
</script>

<template>
    <div class="search-select">
        <UiSingleSelect :model-value="modelValue" :options="options" :groups="groups" :placeholder="placeholder"
            :disabled="disabled" no-results-text="没有找到匹配项" no-options-text="暂无可选项"
            @update:model-value="$emit('update:modelValue', $event)"
            @change="$emit('change', $event)" />
    </div>
</template>

<style scoped>
/* No blue focus ring on the search input / container. */
.search-select :deep(.multiselect-search),
.search-select :deep(.multiselect-search:focus),
.search-select :deep(.multiselect.is-active) {
    outline: none !important;
    box-shadow: none !important;
}

/* Align placeholder, selected label, typed text and dropdown options on the same left edge.
   Library defaults differ (--ms-px 14px vs --ms-option-px 12px) and planer's classes add pl-3. */
.search-select {
    --ms-px: 0.75rem;
    --ms-option-px: 0.75rem;
}

.search-select :deep(.multiselect-placeholder),
.search-select :deep(.multiselect-single-label),
.search-select :deep(.multiselect-search) {
    padding-left: 0.75rem !important;
}

.search-select :deep(.multiselect-option) {
    padding-left: 0.75rem;
}

/* Keep a subtle focus cue: the border turns to the theme colour while open. */
.search-select :deep(.multiselect.is-active) {
    border-color: #a0aa14;
}
</style>
