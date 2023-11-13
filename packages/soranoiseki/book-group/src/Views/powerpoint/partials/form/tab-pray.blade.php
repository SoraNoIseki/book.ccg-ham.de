@php
    if (old('pray')) {
        $content['pray'] = old('pray');
    }
@endphp

<div class="mb-5">
    <label for="pray1" class="mb-3 block text-base font-medium text-main dark:text-light">
        代祷事项（汉堡教会）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="15" name="pray[item1]" property="item1" id="pray1" placeholder="代祷事项（汉堡教会）" 
                class="required w-full resize-none rounded-md border border-gray-200 bg-white dark:bg-gray-900 py-3 px-6 text-base font-medium text-main dark:text-light outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['pray']['item1']){{ $content['pray']['item1'] }}@endisset</textarea>    
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：（无需编号）</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>代祷事项1</li>
                        <li>代祷事项2</li>
                        <li>...</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="pray2" class="mb-3 block text-base font-medium text-main dark:text-light">
        代祷事项（姐妹团契1）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="pray[item2]" property="item2" id="pray2" placeholder="代祷事项（姐妹团契1）" 
                class="required w-full col-span-3 resize-none rounded-md border border-gray-200 bg-white dark:bg-gray-900 py-3 px-6 text-base font-medium text-main dark:text-light outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['pray']['item2']){{ $content['pray']['item2'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>慕尼黑信义华人教会 (München)</li>
                        <li>全年平均聚会人数：80人</li>
                        <li>1. 代祷事项1</li>
                        <li>2. 代祷事项2</li>
                        <li>3. 代祷事项3</li>
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
    <label for="pray3" class="mb-3 block text-base font-medium text-main dark:text-light">
        代祷事项（姐妹团契2）
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="10" name="pray[item3]" property="item3" id="pray3" placeholder="代祷事项（姐妹团契2）" 
                class="required w-full resize-none rounded-md border border-gray-200 bg-white dark:bg-gray-900 py-3 px-6 text-base font-medium text-main dark:text-light outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">@isset($content['pray']['item3']){{ $content['pray']['item3'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>慕尼黑信义华人教会 (München)</li>
                        <li>全年平均聚会人数：80人</li>
                        <li>1. 代祷事项1</li>
                        <li>2. 代祷事项2</li>
                        <li>3. 代祷事项3</li>
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