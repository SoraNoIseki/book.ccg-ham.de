<div class="flex-col space-y-4">
    <div class="px-6">
        <input type="text" class="w-1/3" placeholder="Search books" wire:model.debounce.500ms="search">
    </div>
    
    <table class="min-w-full text-left font-normal">
        <thead class="border-b font-medium dark:border-neutral-500">
            <tr>
                <th scope="col" class="px-6 py-4">Code</th>
                <th scope="col" class="px-6 py-4">Book</th>
                <th scope="col" class="px-6 py-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($books as $book)
                <tr class="border-b dark:border-neutral-500" wire:loading.class="opacity-75">
                    <td class="whitespace-nowrap px-6 py-4">{{ $book->call_nmbr1 }}</td>
                    <td class="whitespace-nowrap px-6 py-4">
                        <p class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $book->topic1 }}</p>
                        <p class="text-normal font-normal text-gray-900 dark:text-gray-100">{{ $book->title }}</p>
                        <p class="text-sm font-normal text-gray-900 dark:text-gray-400">{{ $book->author }} | {{ $book->responsibility_stmt }} </p>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4">
                        @foreach ($book->copies as $copy)
                            <button
                                class="inline-flex flex-col @if($copy->mbrid) bg-red-700 @else bg-green-700 @endif text-gray-50 p-5 rounded-lg mr-2"
                                wire:click="onClickCopy({{$copy->bibid}}, {{$copy->copyid}}, {{$copy->mbrid}})"
                            >
                                <span class="mx-auto">
                                    <svg class="w-6 h-6 text-gray-50 m-auto" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                        <path d="M96 0C43 0 0 43 0 96V416c0 53 43 96 96 96H384h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V384c17.7 0 32-14.3 32-32V32c0-17.7-14.3-32-32-32H384 96zm0 384H352v64H96c-17.7 0-32-14.3-32-32s14.3-32 32-32zm32-240c0-8.8 7.2-16 16-16H336c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16zm16 48H336c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16s7.2-16 16-16z"></path>
                                    </svg>
                                </span>
                                @if ($copy->member)
                                    <span class="mt-2">
                                        {{ $copy->member->full_name }}
                                    </span>
                                @else
                                    <span class="mt-2">
                                        {{ $copy->copy_desc }}
                                    </span>
                                @endif
                                
                            </button>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
        
    </table>

    {{ $books->links() }}
   
</div>
