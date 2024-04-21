<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-main dark:text-gray-200 leading-tight">
            Song
        </h2>
    </x-slot>
    
    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-main dark:text-gray-100">
                    <h3 class="px-6 py-4 font-medium text-xl">{{ $song->name }}</h3>

                    <div class="px-6 py-4">
                        @if (session('success') === false)
                            <div class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                </svg>
                                <span class="sr-only">Info</span>
                                <div>
                                    {{ session('message') }}
                                </div>
                            </div>
                        @endif

                        @if (session('success') === true)
                            <div class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                </svg>
                                <span class="sr-only">Info</span>
                                <div>
                                    {{ session('message') }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4">
                        <p class="my-4"><a href="{{ route('book-group.song.index') . '#' . $song->_id }}"><< 返回列表</a></p>
                        <form action="{{ route('book-group.song.save', ['song' => $song->_id]) }}" method="POST" id="editSong">
                            @csrf

                            <div class="w-full border border-gray-300 bg-blue-50 rounded-md p-4 mb-4 font-semibold">
                                <p class="mb-3">诗歌：{{ $song->name }}</p>
                                <p class="mb-3">乐队：{{ $song->band }}</p>
                                <p class="mb-3">专辑：{{ $song->album }}</p>
                                <p class="mb-3">ID：{{ $song->group_id }}</p>
                            </div>

                            <div class="w-ful mb-4">
                                <button type="submit" class="text-sm w-full px-3 py-2 bg-primary-500 text-white rounded-md font-semibold">保存</button>
                            </div>

                            <div class="w-full flex gap-4 mb-4">
                                <div class="flex gap-3 items-center">
                                    <input type="checkbox" id="song-checked" name="checked" class="rounded-sm" @if ($song->checked) checked @endif />
                                    <label for="song-checked" class="font-semibold">校对完成</label>
                                </div>
                                <div class="flex gap-3 items-center">
                                    <input type="checkbox" id="song-corrected" name="corrected" class="rounded-sm" @if ($song->corrected) checked @endif />
                                    <label for="song-corrected" class="font-semibold">已验收</label>
                                </div>
                            </div>

                            <div class="w-full flex gap-4">
                                <div class="{{ $songContents->count() > 0 ? 'w-1/2' : 'w-full'}}">
                                    <textarea name="text" class="w-full border border-gray-300 bg-gray-50 rounded-md p-4 text-sm font-semibold" rows="50" id="main-song-content">{{$song->script_text_for_ppt_worker}}</textarea>
                                </div>
    
                                @if ($songContents->count() > 0)
                                    <div class="w-1/2">
                                        @foreach ($songContents as $content)
                                            <button type="button" class="apply-song-content mb-2 text-sm w-full px-3 py-2 bg-blue-100 rounded-md font-semibold" data-target="song-content-{{ $content->id }}">应用</button>
                                            <textarea class="w-full mb-4 border-gray-200 bg-blue-50 p-4 text-sm rounded-md" rows="30" id="song-content-{{ $content->id }}">{{ $content->text }}</textarea>
                                        @endforeach
                                    </div>   
                                @endif
                            </div>

                            
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        
    </div>

    
</x-app-layout>