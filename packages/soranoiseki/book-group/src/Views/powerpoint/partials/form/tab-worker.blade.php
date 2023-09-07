@php
    if (old('worker')) {
        $content['worker'] = old('worker');
    }
@endphp

<div class="mb-5">
    <label for="worker1" class="mb-3 block text-base font-medium text-[#07074D]">
        服事列表（本周）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="12" name="worker[item1]" id="worker1" placeholder="服事列表（本周）"
                class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['worker']['item1']){{ $content['worker']['item1'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>2023 年 08 月 16 日主日服事表</li>
                        <li>主题：善仆与恶仆<span class="font-bold text-red-600">+</span>路19:11-28 </li>
                        <li>证道：吴振忠牧师</li>
                        <li>司会：XXX弟兄</li>
                        <li>司琴：XXX姊妹</li>
                        <li>...</li>
                    </ul>
                </x-slot>
            </x-alert>
            <x-alert type="danger" class="mt-5">
                <x-slot name="title">注意事项：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>主题中<span class="font-semibold mx-1">必须</span>包含英文加号，用于分隔主题和经文</li>
                        <li>数字前后建议加上空格</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="worker2" class="mb-3 block text-base font-medium text-[#07074D]">
        服事列表（下周）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="12" name="worker[item2]" id="worker2" placeholder="服事列表（下周）"
                class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['worker']['item2']){{ $content['worker']['item2'] }}@endisset</textarea>
        </div>
    </div>
</div>