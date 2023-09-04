<div class="mb-5">
    <label for="scripture1" class="mb-3 block text-base font-medium text-[#07074D]">
        经文（宣召）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="scripture[item1]" id="scripture1" placeholder="经文（宣召）"
                class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['scripture']['item1']){{ $content['scripture']['item1'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例1：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>诗篇:7[*]</li>
                        <li>诗篇:7[1-5]</li>
                        <li>诗篇:7[1-5,8-9]</li>
                    </ul>
                </x-slot>
            </x-alert>
            <x-alert type="info" class="mt-5">
                <x-slot name="title">格式范例2：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>1. 第一节经文</li>
                        <li>2. 第二节经文</li>
                    </ul>
                </x-slot>
            </x-alert>
            <x-alert type="danger" class="mt-5">
                <x-slot name="title">注意事项：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>方括号及其中所有字符均<span class="font-semibold mx-1">必须</span>为英文字符</li>
                        <li>编号数字后请输入空格</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="scripture2" class="mb-3 block text-base font-medium text-[#07074D]">
        经文（启应经文）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="scripture[item2]" id="scripture2" placeholder="经文（启应经文）"
                class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['scripture']['item2']){{ $content['scripture']['item2'] }}@endisset</textarea>
        </div>
        <div>
            <div class="flex p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    <span class="font-medium">格式范例：（无需编号）</span>
                    <ul class="mt-1.5 list-none list-inside">
                        <li>承认过犯</li>
                        <li>经文（启）</li>
                        <li>经文（应）</li>
                        <li>...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="scripture3" class="mb-3 block text-base font-medium text-[#07074D]">
        经文（读经）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="scripture[item3]" id="scripture3" placeholder="经文（读经）"
        class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['scripture']['item3']){{ $content['scripture']['item3'] }}@endisset</textarea>
        </div>
        <div>
            <div class="flex p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    <span class="font-medium">格式范例：</span>
                    <ul class="mt-1.5 list-none list-inside">
                        <li>格式同宣召</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>