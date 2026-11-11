<x-guest-layout>
    <div class="text-center">
        <h1 class="text-2xl font-bold text-red-500">401</h1>
        <p class="text-xl mt-4 text-gray-800 dark:text-gray-100">{{ __('Unauthorized') }}</p>
        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('Sorry, you are not authorized to access this page.') }}</p>
        <a href="{{ url('/') }}" class="inline-block mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition-colors">{{ __('Return to Homepage') }}</a>
    </div>
</x-guest-layout>