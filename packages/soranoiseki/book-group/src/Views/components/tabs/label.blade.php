<li class="mr-2" role="presentation" >
    <button type="button" role="tab" aria-controls="{{ $id }}" aria-selected="false" @click="tab = '{{ $id }}'"
        class="inline-block px-4 py-2 rounded-t-md" :class="{'text-white bg-primary-700': tab == '{{ $id }}', 'border-transparent hover:text-primary-600': tab != '{{ $id }}'}">{{ $label }}</button>
</li>