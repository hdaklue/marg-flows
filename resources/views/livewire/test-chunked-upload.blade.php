<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Chunked Upload Test
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Test the chunked file upload functionality with large files.
            </p>
            
            <form wire:submit="submit">
                {{ $this->form }}
                
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button
                        type="button"
                        class="text-sm font-semibold leading-6 text-gray-900 dark:text-white"
                        wire:click="$refresh"
                    >
                        Reset
                    </button>
                    <button
                        type="submit"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                    >
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Debug Info -->
    <div class="mt-6 bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Debug Info</h3>
        <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-auto">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>