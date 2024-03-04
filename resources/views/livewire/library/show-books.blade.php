<div class="flex-col space-y-4">
    <div class="px-6">
        <div class="w-full md:max-w-[400px] flex space-x-3 justify-between">
            <input type="text" class="w-full rounded-md border-gray-300" placeholder="搜索书名/编号/ISBN/作者" wire:model.debounce.500ms="search">
        </div>
    </div>

    <div class="px-6">
        <div class="w-full flex space-x-3 justify-between">
            <button class="whitespace-norwrap w-full rounded-lg p-2 text-sm {{ !$returnMode ? 'text-primary-600 border border-primary-600' : 'bg-primary-600 border text-white'}}" wire:click="toggleReturnMode()">
                {{ !$returnMode ? '还书模式' : '列表模式' }}
            </button>
            <button class="whitespace-norwrap w-full rounded-lg p-2 text-sm {{ !$externalMode ? 'text-primary-600 border border-primary-600' : 'bg-primary-600 border text-white'}}" wire:click="toggleExternalMode()">
                {{ !$externalMode ? '只显示中文图书馆书籍' : '显示全部书籍' }}
            </button>
        </div>
       
    </div>

    <div class="px-6">
        {{ $books->links() }}
    </div>
    
    @if ($books->count() > 0)
        <table class="min-w-full text-left font-normal">
            <thead class="border-b font-medium dark:border-neutral-500">
                <tr>
                    <th scope="col" class="px-6 py-4 w-24">编码</th>
                    <th scope="col" class="px-6 py-4">书籍</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($books as $book)
                    <tr class="border-b dark:border-neutral-500" wire:loading.class="opacity-75">
                        <td class="whitespace-nowrap px-6 py-4 align-top">
                            <p class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $book->topic1 }}</p>
                            <p class="text-base font-normal text-gray-600 dark:text-gray-400">{{ $book->call_nmbr1 }}</p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <p class="text-base font-normal text-gray-900 dark:text-gray-100">{{ $book->title }}</p>
                            @if ($book->author != '' && $book->responsibility_stmt != '')
                                <p class="text-sm font-normal text-gray-900 dark:text-gray-400">
                                    <span>{{ $book->author }}</span>
                                    @if ($book->author != '' && $book->responsibility_stmt != '')
                                        <span> | </span>
                                    @endif
                                    <span>{{ $book->responsibility_stmt }}</span>
                                </p>
                            @endif
                        
                        
                            <div class="w-full mt-2 flex flex-wrap flex-row gap-2">
                                @foreach ($book->copies as $copy)
                                    <button
                                        class="text-sm inline-flex flex-col {{ $copy->mbrid ? 'bg-red-700' : 'bg-green-700' }} text-gray-50 p-2 rounded-lg"
                                        wire:click="onClickCopy({{$copy->bibid}}, {{$copy->copyid}}, {{$copy->mbrid}})"
                                    >
                                        @if ($copy->member)
                                            <span>{{ $copy->member->full_name }}</span>
                                        @else
                                            <span class="">{{ $copy->copy_desc }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="w-full px-6">
            <p class="text-md w-full text-center"> ╮(╯▽╰)╭<br>没有找到符合的书籍</p>
        </div>
    @endif

    

    <div class="px-6 mb-4">
        {{ $books->links() }}
    </div>
    
   
</div>
