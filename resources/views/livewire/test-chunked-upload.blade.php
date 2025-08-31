<div class="max-w-4xl p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-sm dark:bg-gray-800">
        <div class="p-6">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
                Chunked Upload Test
            </h1>
            <p class="mb-6 text-gray-600 dark:text-gray-400">
                Test the chunked file upload functionality with large files.
            </p>

            <form wire:submit="submit">
                {{ $this->form }}

                <div class="flex items-center justify-end mt-6 gap-x-6">
                    <button
                        type="button"
                        class="text-sm font-semibold leading-6 text-gray-900 dark:text-white"
                        wire:click="$refresh"
                    >
                        Reset
                    </button>
                    <button
                        type="submit"
                        class="px-3 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                    >
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Debug Info -->
    <div class="p-4 mt-6 bg-gray-100 rounded-lg dark:bg-gray-700">
        <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Debug Info</h3>
        <pre class="overflow-auto text-xs text-gray-700 dark:text-gray-300">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
