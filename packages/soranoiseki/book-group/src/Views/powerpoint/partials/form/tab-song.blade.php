@php
    if (old('song')) {
        $content['song'] = old('song');
    }
@endphp

<div class="mb-5">
    <label for="song1" class="mb-3 block text-base font-medium text-[#07074D]">
        诗歌1
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="15" name="song[item1]" id="song1" placeholder="诗歌1"
                class="required w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['song']['item1']){{ $content['song']['item1'] }}@endisset</textarea>
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>住在你里面</li>
                        <li>赞美之泉《相信有爱就有奇迹》</li>
                        <li>&nbsp;</li>
                        <li>在干旱无水之地我渴慕祢</li>
                        <li>在旷野无人之处我寻求祢</li>
                        <li>...</li>
                    </ul>
                </x-slot>
            </x-alert>
            <x-alert type="danger" class="mt-5">
                <x-slot name="title">注意事项：</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>前两行为<span class="font-semibold mx-1">标题</span>和<span class="font-semibold mx-1">出处</span></li>
                        <li>诗歌段落用<span class="font-semibold mx-1">空行</span>分隔；如果没有分隔则自动按照每五行一页自动分割</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="song2" class="mb-3 block text-base font-medium text-[#07074D]">
        诗歌2
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="15" name="song[item2]" id="song2" placeholder="诗歌2"
                class="required w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['song']['item2']){{ $content['song']['item2'] }}@endisset</textarea>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="song3" class="mb-3 block text-base font-medium text-[#07074D]">
        诗歌3
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="15" name="song[item3]" id="song3" placeholder="诗歌3"
                class="required w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['song']['item3']){{ $content['song']['item3'] }}@endisset</textarea>
        </div>
    </div>
</div>

<div class="mb-5">
    <label for="song4" class="mb-3 block text-base font-medium text-[#07074D]">
        回应诗歌
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3">
            <textarea rows="15" name="song[item4]" id="song4" placeholder="回应诗歌"
                class="required w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-indigo-600 focus:shadow-md">@isset($content['song']['item4']){{ $content['song']['item4'] }}@endisset</textarea>
        </div>
    </div>
</div>
