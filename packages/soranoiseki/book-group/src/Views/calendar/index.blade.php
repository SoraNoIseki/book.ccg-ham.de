<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Calendar
        </h2>
    </x-slot>

    
    <div class="py-12">
       
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($calendar)
                        <h3 class="px-6 py-4 font-medium text-xl">Calendar {{ $calendar->year }}</h3>
                    @else
                        <h3 class="px-6 py-4 font-medium text-xl">Calendar</h3>
                        <form action="{{ route('book-group.calendar.create') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-lg bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded">创建新日历</a>
                        </form>
                        
                    @endif
                </div>
            </div>
        </div>
        
        @if ($calendar)
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="md:flex">
                        <div class="p-6 text-gray-900 dark:text-gray-100 w-full">
                            <div class="px-6 py-4">
                                <form method="POST" action="{{ route('book-group.calendar.import-events', ['calendar' => $calendar->id]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="flex place-content-between mb-2">
                                        <h3 class="font-medium text-lg">上传大事历</h3>
                                        <button type="submit" class="text-primary-600 hover:text-primary-700 font-semibold rounded-lg dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                            <svg class="w-4 h-4 inline-block relative top-[-2px]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                            上传
                                        </button>
                                    </div>
                                   
                                    <div class="flex items-center justify-center w-full">
                                        <label for="file-events" class="flex flex-col items-center justify-center w-full py-4 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                                </svg>
                                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">点击上传</span> 或 将文件拖入输入框</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">仅限 CSV 文件</p>
                                            </div>
                                            <input id="file-events" type="file" name="file" class="hidden" />
                                        </label>
                                    </div> 
                                </form>
                            </div>
                        </div>
                        <div class="p-6 text-gray-900 dark:text-gray-100 w-full">
                            <div class="px-6 py-4">
                                <form method="POST" action="{{ route('book-group.calendar.import-bible-texts', ['calendar' => $calendar->id]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="flex place-content-between mb-2">
                                        <h3 class="font-medium text-lg">上传每月经文</h3>
                                        <button type="submit" class="text-primary-600 hover:text-primary-700 font-semibold rounded-lg dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                            <svg class="w-4 h-4 inline-block relative top-[-2px]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                            上传
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-center w-full">
                                        <label for="file-bible-texts" class="flex flex-col items-center justify-center w-full py-4 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                                </svg>
                                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">点击上传</span> 或 将文件拖入输入框</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">仅限CSV</p>
                                            </div>
                                            <input id="file-bible-texts" type="file" name="file" class="hidden" />
                                        </label>
                                    </div> 
                                </form>
                            </div>
                        </div>  
                    </div>
                                    
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="px-6">
                            <a href="{{ route('book-group.calendar.preview', ['calendar' => $calendar->id]) }}" target="_blank"
                                class="btn btn-lg bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded">预览日历</a>
                        </div>
                    </div>
                </div>
            </div>


            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="font-medium text-lg mb-2 pl-6">大事历</h3>
                        <div class="relative overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">
                                            Date
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Event
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($events as $event)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                {{ $event->date }}
                                            </th>
                                            <td class="px-6 py-4">
                                                {!! str_replace('|', '<br>', $event->name) !!}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div> 
                    </div>    
                </div>
            </div>


            @if ($calendar->details->count() > 0)
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="font-medium text-lg mb-2 pl-6">每月经文</h3>
                            <div class="relative overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">
                                                Month
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Bible
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($calendar->details as $detail)
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                    {{ $detail->month }}
                                                </th>
                                                <td class="px-6 py-4">
                                                    {!! str_replace('|', '<br>', $detail->bible_text) !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>    
                    </div>
                </div>
            @endif
        @endif
        
       
    </div>
</x-app-layout>