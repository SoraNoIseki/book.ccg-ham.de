<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-main dark:text-gray-200 leading-tight">
            Powerpoint
        </h2>
    </x-slot>
    
    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-main dark:text-gray-100">
                    <h3 class="px-6 py-4 font-medium text-xl">诗歌管理</h3>

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
                        <div class="relative overflow-x-auto mb-6">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">
                                            诗歌名称
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            乐队
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            专辑
                                        </th>
                                        <th scope="col" class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($unsavedSongContents as $songContent)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                            <td class="px-6 py-4">
                                                <span class="toggle-song-content cursor-pointer" data-target="song-content-{{ $songContent->id }}">
                                                    {{ $songContent->name }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ $songContent->band }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ $songContent->album }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="{{ route('book-group.song-content.upload', ['songContent' => $songContent->id]) }}">上传</a>
                                            </td>
                                        </tr>
                                        <tr class="hidden bg-white border-b dark:bg-gray-800 dark:border-gray-700" id="song-content-{{ $songContent->id }}">
                                            <td colspan="4" class="px-2 py-2">
                                                <textarea class="w-full my-2 border-gray-200 bg-blue-50 p-4 text-sm rounded-md" rows="30">{{ $songContent->text }}</textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php
                            $countAll = $songs->count();
                            $count = 0;
                        @endphp

                        <div class="relative overflow-x-auto">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3"></th>
                                        <th scope="col" class="px-6 py-3">
                                            诗歌名称
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            乐队
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            专辑
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            已校对
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            计数
                                        </th>
                                        <th scope="col" class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($songs as $song)
                                        @php
                                            $count++;
                                        @endphp

                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700" id="{{ $song->_id }}">
                                            <td class="px-6 py-4">
                                                {{ $count }} / {{ $countAll }}
                                            </td>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                <a href="{{ route('book-group.song.edit', ['song' => $song->_id]) }}">{{ $song->name }}</a><br>
                                                <span class="text-xs text-gray-500"> {{ $song->group_id }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ $song->band }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ $song->album }}
                                            </td>
                                            <td class="px-6 py-4 {{ $song->checked ? 'text-green-700' : 'text-red-600'}}">
                                                {{ $song->checked ? '是' : '否' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ isset($songContentsCount[$song->name]) ? $songContentsCount[$song->name] : '' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="{{ route('book-group.song.delete', ['song' => $song->_id]) }}">删除</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</x-app-layout>