<div class="w-full p-5">
    <form wire:submit.prevent="submit">
        <h3 class="block uppercase tracking-wide text-gray-700 text-xl font-bold mb-3">{{ __('library.modal.title_borrow') }}</h3>
        <div class="block mb-3">
            <p class="block uppercase tracking-wide text-gray-700 text-normal font-bold">{{ __('library.modal.book_title') }}{{ $book->title }} ({{ $book->call_nmbr1 }})</p>
        </div>
        <div class="block mb-3">
            <label class="block uppercase tracking-wide text-gray-700 text-normal font-bold mb-2" for="member">
                {{ __('library.modal.select_member') }}
            </label>
            <select id="member" wire:model="selectedMember"
                class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500">
                <option value="">{{ __('generic.please_select') }}</option>
                @foreach ($members as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('selectedMember') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
        </div>
        <div class="flex mb-3 justify-between space-x-2">
            <button type="submit"
                class="w-full uppercase text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ __('library.modal.button_borrow') }}
            </button>
            <button type="button" wire:click="close"
                class="w-full uppercase text-white bg-gray-400 hover:bg-gray-500 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ __('generic.cancel') }}
            </button>
        </div>
    </form>

    <hr class="h-px my-8 bg-gray-200 border-0 dark:bg-gray-700">

    <div class="block mb-3" x-data="{ open: false }">
        <div class="flex justify-between mb-3">
            <p class="block uppercase tracking-wide text-gray-700 text-normal font-bold">或创建新用户</p>
            <button @click="open = ! open">
                <span x-show="!open">+ 创建用户</span>
                <span x-show="open">x 折叠</span>
            </button>
        </div>
        
        <form wire:submit.prevent="createAndSubmit" x-show="open">
            <div class="mb-3 flex space-x-2">
                <div class="w-full">
                    <label for="lastname" class="block mb-2 font-medium text-gray-900 dark:text-white">姓<span class="text-red-700 ml-1">*</span></label>
                    <input type="text" id="lastname" wire:model.debounce.1000ms="lastname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                    @error('lastname') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
                </div>
                <div class="w-full">
                    <label for="firstname" class="block mb-2 font-medium text-gray-900 dark:text-white">名<span class="text-red-700 ml-1">*</span></label>
                    <input type="text" id="firstname" wire:model.debounce.1000ms="firstname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                    @error('firstname') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="block mb-2 font-medium text-gray-900 dark:text-white">E-Mail<span class="text-red-700 ml-1">*</span></label>
                <input type="email" id="email" wire:model.debounce.1000ms="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('email') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="mb-3">
                <label for="telephone" class="block mb-2 font-medium text-gray-900 dark:text-white">电话</label>
                <input type="tel" id="telephone" wire:model.debounce.1000ms="telephone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                @error('telephone') <p class="text-sm text-red-600 mt-1 block">{{ $message }}</p> @enderror
            </div>
            <div class="flex mb-3 justify-between space-x-2">
                <button type="submit"
                    class="w-full uppercase text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                    创建并{{ __('library.modal.button_borrow') }}
                </button>
                <button type="button" wire:click="close"
                    class="w-full uppercase text-white bg-gray-400 hover:bg-gray-500 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                    {{ __('generic.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>
