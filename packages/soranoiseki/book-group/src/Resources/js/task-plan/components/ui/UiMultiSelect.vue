<template>
    <div class="flex flex-row items-center min-w-[180px] w-full">
        <Multiselect
            :options="options"
            mode="tags"
            :close-on-select="true"
            :create-option="false"
            :placeholder=placeholder
            noResultsText="Keine Ergebnisse gefunden"
            noOptionsText="Keine Optionen verfügbar"
            :searchable="true"
            v-model="selectedValues"
            class="w-full"
            :classes="{
                containerActive:'',
                search:'multiselect-search cursor-pointer focus-within:ring-2 focus-within:ring-blue-600',
                optionSelected:'bg-white',
                optionPointed:'bg-gray-100',
                optionSelectedPointed:'bg-gray-100',
                container: 'relative flex items-center justify-end border-0 p-[1px] ring-1 ring-inset ring-gray-600 focus-within:ring-2 focus-within:ring-blue-600 bg-white text-sm leading-6 outline-none',
                tagsSearch: 'absolute inset-0 border-0 outline-none focus:ring-0 appearance-none p-0 text-sm font-sans box-border w-full',
                dropdown: 'max-h-60 absolute left-0 right-0 bottom-0 transform translate-y-full border border-gray-300 -mt-px overflow-y-scroll z-50 bg-white flex flex-col rounded-b text-sm',
                dropdownTop: '-translate-y-full top-px bottom-auto rounded-b-none rounded-t',
                dropdownHidden: 'hidden',
                tag: 'bg-blue-600 text-white text-xs font-semibold py-0.5 pl-2 rounded mr-1 mb-1 flex items-center whitespace-nowrap',
                placeholder: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3 pr-8 text-gray-400',
                tagDisabled: 'pr-2 opacity-50 rtl:pl-2',
                optionDisabled: 'text-gray-300 opacity-20 cursor-not-allowed',
                containerDisabled: 'cursor-default bg-gray-100',
            }"
            @change="onChange"
            @clear="onClear"
            :id="id"
            :disabled="props.disabled"
        >
            <template v-slot:tag="{ option, handleTagRemove, disabled }">
                <slot name="tag" :option="option" :handleTagRemove="handleTagRemove" :disabled="disabled"></slot>
            </template>
        </Multiselect>
    </div>

</template>

<script lang="ts" setup>

import { ref, PropType, onMounted, watch } from 'vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    modelValue: {
        type: Array as PropType<string[] | number[]>,
        default: ''
    },
    label: {
        type: String,
        required: false,
        default: '',
    },
    placeholder: {
        type: String,
        required: false,
        default: 'Bitte wählen...',
    },
    options: {
        type: Array as PropType<SelectOption[]>,
        required: true,
        default: () => []
    },
    required: {
        type: Boolean,
        required: false,
        default: false,
    },
    disabled: {
        type: Boolean,
        required: false,
        default: false,
    },
});



onMounted(() => {
    id.value = generateId();
});

const id = ref('select');
const selectedValues = ref(props.modelValue);

const emit = defineEmits(['change', 'update:modelValue']);

const onChange = (newValue) => {
    emit('change', newValue);
}

const onClear = () => {
    selectedValues.value = [];
    emit('change', []);
}

const generateId = (): string => {
    return 'select-' + Math.random().toString(36).substring(2, 7);
}

// watch for changes from inside
watch(selectedValues, (newValue) => {
    emit('update:modelValue', newValue);
});

// watch for changes from outside
watch(() => props.modelValue, (newValue) => {
    if (newValue === selectedValues.value) {
        return;
    }
    selectedValues.value = newValue ?? '';
});


</script>


<style scoped>

:deep(.multiselect-option) {
    @apply text-sm text-gray-700;
}

:deep(.is-disabled .multiselect-wrapper) {
    @apply !cursor-not-allowed;
}

</style>
