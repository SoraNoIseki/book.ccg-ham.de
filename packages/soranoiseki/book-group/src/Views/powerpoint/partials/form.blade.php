<form method="POST" id="pptGenerator">
    @csrf

    @if ($errors->any())
        <div class="mb-5 bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4" role="alert">
            <div class="alert alert-danger">
                <p class="font-bold">发生错误。改动暂存中，请勿刷新页面！</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mb-5 -mx-3 flex flex-wrap">
        <div class="w-full px-3 sm:w-1/2">
            <div class="mb-5">
                <label for="date" class="mb-3 block text-base font-medium text-main">
                    日期
                </label>
                <input type="date" id="date" disabled value="{{ $date }}"
                    class="w-full rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md" />
                <input type="hidden" name="date" id="dateHidden" value="{{ $date }}">
            </div>
        </div>

        <div class="w-full px-3 sm:w-1/2">
            <div class="mb-5">
                <label for="version" class="mb-3 block text-base font-medium text-main">
                     加载其他版本
                </label>
                <div class="flex gap-4" x-data="{ selectedDate: '{{ $date }}'}">
                    <select id="version" x-on:change="selectedDate = $event.target.value"
                        class="w-full rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">
                        <option value="{{ $date }}">选择...</option>
                        <option value="{{ $defaultDate }}">本周</option>
                        @foreach ($versions as $version)
                            <option value='{{ $version }}'>{{ $version }}</option>
                        @endforeach
                    </select>
                    <a :href="'{{ route('book-group.ppt.index', ['v' => '']) }}' + selectedDate"
                        class="block px-5 py-3 w-1/3 text-sm font-medium text-center text-white bg-primary-600 rounded-lg cursor-pointer">
                        <span class="whitespace-nowrap">加载</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <x-tabs-container defaultTab="pray">
        <x-slot name="labels">
            <x-tabs-label id="pray" label="代祷"></x-tabs-label>
            <x-tabs-label id="scripture" label="经文"></x-tabs-label>
            <x-tabs-label id="preach" label="讲道"></x-tabs-label>
            <x-tabs-label id="song" label="诗歌"></x-tabs-label>
            <x-tabs-label id="report" label="报告"></x-tabs-label>
            <x-tabs-label id="worker" label="服事"></x-tabs-label>
        </x-slot>

        <x-slot name="tabs">
            <x-tabs-tab id="pray">
                @include('book-group::powerpoint.partials.form.tab-pray')
            </x-tabs-tab>
            <x-tabs-tab id="scripture">
                @include('book-group::powerpoint.partials.form.tab-scripture')
            </x-tabs-tab>
            <x-tabs-tab id="preach">
                @include('book-group::powerpoint.partials.form.tab-preach')
            </x-tabs-tab>
            <x-tabs-tab id="song">
                @include('book-group::powerpoint.partials.form.tab-song')
            </x-tabs-tab>
            <x-tabs-tab id="report">
                @include('book-group::powerpoint.partials.form.tab-report')
            </x-tabs-tab>
            <x-tabs-tab id="worker">
                @include('book-group::powerpoint.partials.form.tab-worker')
            </x-tabs-tab>
        </x-slot>
        
        <div>
            @if ($saveEnabled)
                <button type="submit" formaction="{{ route('book-group.ppt.store') }}" id="saveButton"
                    class="hover:shadow-form rounded-md bg-primary-600 py-3 px-8 text-center text-base font-semibold text-white outline-none">
                    保存
                </button>
                <button type="submit" formaction="{{ route('book-group.ppt.store-download') }}" id="downloadButton"
                    class="hover:shadow-form rounded-md bg-primary-700 py-3 px-8 text-center text-base font-semibold text-white outline-none">
                    保存并下载
                </button>
            @else
                <button type="submit" formaction="{{ route('book-group.ppt.download') }}" id="downloadButton"
                    class="hover:shadow-form rounded-md bg-primary-700 py-3 px-8 text-center text-base font-semibold text-white outline-none">
                    下载
                </button>
            @endif
            <button class="hidden opacity-50 cursor-not-allowed" disabled></button>
            
        </div>
    </x-tabs-container>
</form>

<script>

</script>
