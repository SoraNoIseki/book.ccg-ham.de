@php
    if (old('report')) {
        $content['report'] = old('report');
    }
@endphp

<div class="mb-5">
    <label class="mb-3 block text-base font-medium text-main">
        报告
    </label>

    <div class="w-full grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="col-span-3" id="reportList">
            @foreach ($content['report'] as $key => $report)
                <div class="report-item flex mb-3">
                    @php $index = str_replace('item', '', $key); @endphp
                    <textarea rows="3" name="report[{{ $key }}]" placeholder="请填写报告事项..."
                        class="w-full resize-none rounded-md border border-gray-200 bg-white py-3 px-6 text-base font-medium text-main outline-none focus:ring-primary-500 focus:border-primary-500 focus:shadow-md">{{ $report }}</textarea>
                    <div class="actions ml-3 flex flex-col">
                        <svg class="move w-6 h-6 my-auto text-gray-500 hover:text-primary-700 cursor-pointer" xmlns="http://www.w3.org/2000/svg" fill="currentColor" stroke="none" viewBox="0 0 24 24"><path d="M8 16H4l6 6V2H8zm6-11v17h2V8h4l-6-6z"></path></svg>
                        <svg class="delete w-6 h-6 my-auto text-gray-500 hover:text-red-600 cursor-pointer" version="1.2" baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4c-4.419 0-8 3.582-8 8s3.581 8 8 8 8-3.582 8-8-3.581-8-8-8zm3.707 10.293c.391.391.391 1.023 0 1.414-.195.195-.451.293-.707.293s-.512-.098-.707-.293l-2.293-2.293-2.293 2.293c-.195.195-.451.293-.707.293s-.512-.098-.707-.293c-.391-.391-.391-1.023 0-1.414l2.293-2.293-2.293-2.293c-.391-.391-.391-1.023 0-1.414s1.023-.391 1.414 0l2.293 2.293 2.293-2.293c.391-.391 1.023-.391 1.414 0s.391 1.023 0 1.414l-2.293 2.293 2.293 2.293z"></path></svg>
                        <svg class="add w-6 h-6 my-auto text-gray-500 hover:text-primary-700 cursor-pointer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><g id="Edit / Add_To_Queue"><path id="Vector" d="M3 9V19.4C3 19.9601 3 20.2399 3.10899 20.4538C3.20487 20.642 3.35774 20.7952 3.5459 20.8911C3.7596 21 4.0395 21 4.59846 21H15.0001M14 13V10M14 10V7M14 10H11M14 10H17M7 13.8002V6.2002C7 5.08009 7 4.51962 7.21799 4.0918C7.40973 3.71547 7.71547 3.40973 8.0918 3.21799C8.51962 3 9.08009 3 10.2002 3H17.8002C18.9203 3 19.4801 3 19.9079 3.21799C20.2842 3.40973 20.5905 3.71547 20.7822 4.0918C21.0002 4.51962 21.0002 5.07969 21.0002 6.19978L21.0002 13.7998C21.0002 14.9199 21.0002 15.48 20.7822 15.9078C20.5905 16.2841 20.2842 16.5905 19.9079 16.7822C19.4805 17 18.9215 17 17.8036 17H10.1969C9.07899 17 8.5192 17 8.0918 16.7822C7.71547 16.5905 7.40973 16.2842 7.21799 15.9079C7 15.4801 7 14.9203 7 13.8002Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></g></svg>
                    </div>
                </div>
            @endforeach
        </div>
        <div>
            <x-alert type="info">
                <x-slot name="title">格式范例：（无需编号）</x-slot>
                <x-slot name="content">
                    <ul class="mt-1.5 list-none list-inside">
                        <li>每个输入框为一页报告事项</li>
                        <li>可以换行</li>
                    </ul>
                </x-slot>
            </x-alert>
        </div>
    </div>
</div>