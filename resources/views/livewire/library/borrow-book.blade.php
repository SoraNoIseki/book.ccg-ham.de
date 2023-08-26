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
        <div class="flex mb-3">
            <button type="submit"
                class="w-full mb-1 uppercase text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-normal px-5 py-2.5 text-center inline-flex items-center mr-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                {{ __('library.modal.button_borrow') }}
            </button>
            <button type="button" wire:click="close"
                class="w-full uppercase text-white bg-gray-400 hover:bg-gray-500 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-normal px-5 py-2.5 text-center inline-flex items-center mr-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                {{ __('generic.cancel') }}
            </button>
        </div>
    </form>
</div>
