<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-main dark:text-gray-200 leading-tight">
            诗歌管理
        </h2>
    </x-slot>

    <div class="sm:py-12">
        <div id="app-song-admin" data-song-id="{{ $songId ?? '' }}"></div>
    </div>
</x-app-layout>
