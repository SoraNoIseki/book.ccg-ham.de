<select data-ajax-load data-target="{{ $target }}" data-url="{{ route('book-group.ppt.ajax.get-song') }}"
    class="song-selector bg-gray-50 border border-gray-300 text-main text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-48 mb-3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
    <option value="">从歌库中选择...</option>
    @foreach ($songs as $song)
        <option value="{{ $song->_id }}">{{ $song->song_id }}</option>
    @endforeach
</select>