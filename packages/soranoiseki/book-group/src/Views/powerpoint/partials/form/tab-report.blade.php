<div class="mb-5">
    <label class="mb-3 block text-base font-medium text-[#07074D]">
        报告
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            @for ($i = 1; $i <= 12; $i++)
            <textarea rows="3" name="report[item{{ $i }}]" id="report{{ $i }}" placeholder="报告事项 {{ $i }}"
                class="w-full mb-3 resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['report']['item'.$i]){{ $content['report']['item'.$i] }}@endisset</textarea>
            @endfor
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：（无需编号）</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>每个输入框一个报告事项</li>
                        <li>可以换行</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>