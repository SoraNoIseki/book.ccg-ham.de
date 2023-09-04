<li class="mr-2" role="presentation" >
    <button type="button" role="tab" aria-controls="{{ $id }}" aria-selected="false" @click="tab = '{{ $id }}'"
        class="inline-block p-4 border-b-2 rounded-t-lg" :class="{'text-indigo-600 border-indigo-600': tab == '{{ $id }}', 'border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300': tab != '{{ $id }}'}">{{ $label }}</button>
</li>