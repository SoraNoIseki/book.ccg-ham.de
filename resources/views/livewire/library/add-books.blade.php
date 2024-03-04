<div class="px-6">
    <div class="w-full">
        <form wire:submit.prevent="create" x-show="open">
            <div class="mb-3">
                <label for="isbn" class="block mb-2 font-medium text-gray-900 dark:text-white">
                    ISBN<span class="text-red-700 ml-1">*</span>
                    <span class="inline-block w-4 h-4 rounded-[50%] {{ $apiStatus < 0 ? 'bg-gray-600' : ($apiStatus == 0 ? 'bg-red-600' : ($apiStatus == 1 ? 'bg-green-600' : 'bg-yellow-600')) }}"></span>
                </label>
                <input type="text" id="isbn" wire:model.debounce.1000ms="isbn" wire:input.debounce.1000ms="onInputISBN($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('isbn') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="title" class="block mb-2 font-medium text-gray-900 dark:text-white">书名<span class="text-red-700 ml-1">*</span></label>
                <input type="text" id="title" wire:model.debounce.1000ms="title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('title') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="author" class="block mb-2 font-medium text-gray-900 dark:text-white">作者</label>
                <input type="text" id="author" wire:model.debounce.1000ms="author" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('author') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="publisher" class="block mb-2 font-medium text-gray-900 dark:text-white">出版社</label>
                <input type="text" id="publisher" wire:model.debounce.1000ms="publisher" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('publisher') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="code" class="block mb-2 font-medium text-gray-900 dark:text-white">内部编号</label>
                <input type="text" id="code" wire:model.debounce.1000ms="code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('code') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="quantity" class="block mb-2 font-medium text-gray-900 dark:text-white">数量</label>
                <input type="number" id="quantity" wire:model.debounce.1000ms="quantity" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('quantity') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="category" class="block mb-2 font-medium text-gray-900 dark:text-white">分类</label>
                <select id="category" wire:model.debounce.1000ms="category"
                    class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500">
                    {{-- <option value="无分类">无分类</option> --}}
                    <option value="中文图书馆">FMCD 中文图书馆</option>
                    <option value="释经书籍">A 释经书籍</option>
                    <option value="培训成长">B 培训成长</option>
                    <option value="小说散文">C 小说散文</option>
                    <option value="诗歌">D 诗歌</option>
                    <option value="儿童主日学教材">E 儿童主日学教材</option>
                </select>                
                @error('category') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>

            <div class="flex mb-3 justify-between space-x-2">
                <button type="submit"
                    class="w-full uppercase text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                    创建
                </button>
            </div>
        </form>
    </div>
</div>