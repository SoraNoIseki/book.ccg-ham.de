@php
    if (old('preach')) {
        $content['preach'] = old('preach');
    }
@endphp

<div class="mb-5">
    <label for="preach0" class="mb-3 block text-base font-medium text-main">
        讲道标题
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="5" name="preach[item0]" id="preach0" placeholder="讲道标题"
                class="required w-full resize-none rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['preach']['item0']){{ $content['preach']['item0'] }}@endisset</textarea>
        </div>
        <div>
            <div class="flex p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    <span class="font-medium">格式范例：</span>
                    <ul class="mt-1.5 list-none list-inside">
                        <li>善仆与恶仆</li>
                        <li>吴振忠牧师证道</li>
                        <li>路加福音 19:11-28</li>
                    </ul>
                </div>
            </div>
            <div class="flex p-4 mt-5 text-sm text-orange-800 rounded-lg bg-orange-50 dark:bg-gray-800 dark:text-orange-300" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    <span class="font-medium">注意事项：</span>
                    <ul class="mt-1.5 list-none list-inside">
                        <li>第一行为讲道标题，之后几行均为副标题</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="preach1" class="mb-3 block text-base font-medium text-main">
        讲道（引言）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="preach[item1]" id="preach1" placeholder="讲道（引言）"
                class="required w-full resize-none rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['preach']['item1']){{ $content['preach']['item1'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>1. 引言1</li>
                        <li>2. 引言2</li>
                        <li>3. ...</li>
                    </ul>
                </x-slot>
            </x-alert>
            <x-alert type="danger" class="mt-5">
                <x-slot name="title">注意事项：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>编号数字后请输入空格</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="preach2" class="mb-3 block text-base font-medium text-main">
        讲道（经文理解与应用）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="preach[item2]" id="preach2" placeholder="讲道（经文理解与应用）"
                class="required w-full resize-none rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['preach']['item2']){{ $content['preach']['item2'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>1. 大纲1</li>
                        <li>2. 大纲2</li>
                        <li>3. ...</li>
                    </ul>
                </x-slot>
            </x-alert>
            <x-alert type="danger" class="mt-5">
                <x-slot name="title">注意事项：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>编号数字后请输入空格</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="preach3" class="mb-3 block text-base font-medium text-main">
        讲道（总结）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="preach[item3]" id="preach3" placeholder="讲道（总结）"
                class="required w-full resize-none rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['preach']['item3']){{ $content['preach']['item3'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="danger">
                <x-slot name="title">注意事项：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>可输入多行，但一般情况下不需要编号</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>
