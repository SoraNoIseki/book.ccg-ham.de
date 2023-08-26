<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Library
        </h2>
    </x-slot>

    

    
    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="px-6 py-4 font-medium text-xl">Books ({{ $books->count() }})</h3>
                    <livewire:library.show-books />
                </div>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="px-6 py-4 font-medium text-xl">Import Books</h3>
                    <div class="px-6 py-4">
                        <form method="POST" action="{{ route('library.import.books') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="file" class="shadow appearance-none border border-gray-400 rounded w-full py-3 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" />
                            <input class="btn btn-lg bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit" value="Upload" />
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="px-6 py-4 font-medium text-xl">Members ({{ $members->count() }})</h3>
                    <table class="min-w-full text-left font-light">
                        <thead class="border-b font-medium dark:border-neutral-500">
                            <tr>
                                <th scope="col" class="px-6 py-4">Name</th>
                                <th scope="col" class="px-6 py-4">E-Mail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr class="border-b dark:border-neutral-500 font-normal">
                                    <td class="whitespace-nowrap px-6 py-4">{{ $member->full_name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $member->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>