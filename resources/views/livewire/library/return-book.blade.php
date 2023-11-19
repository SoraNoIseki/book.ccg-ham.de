<div class="w-full p-5">
    <form wire:submit.prevent="create">
        <h3 class="block uppercase tracking-wide text-gray-700 text-xl font-bold mb-3">{{ __('library.modal.title_return') }}</h3>
        <div class="block mb-3">
            <p class="block uppercase tracking-wide text-gray-700 text-normal font-bold">{{ __('library.modal.book_title') }}{{ $book->title }} ({{ $book->call_nmbr1 }})</p>
        </div>
        <div class="block mb-3">
            <p class="text-gray-700 text-normal font-bold">
                {{ __('library.modal.borrowed_member') }}{{ $member->full_name }}
            </p>
        </div>
        <div class="flex mb-3 space-x-2 justify-between">
            <button type="submit"
                class="w-full uppercase text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ __('library.modal.button_return') }}
            </button>
            <button type="submit"
                class="w-full uppercase text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-md text-normal px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                续借
            </button>
            <button type="button" wire:click="close"
                class="w-full uppercase text-white bg-gray-400 hover:bg-gray-500 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-md text-normal px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ __('generic.cancel') }}
            </button>
        </div>
    </form>
</div>
