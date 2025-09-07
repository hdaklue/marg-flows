<div class="mx-auto max-w-4xl p-6">
    <div class="rounded-lg bg-white shadow-sm dark:bg-zinc-800">
        <div class="p-6">
            <h1 class="mb-2 text-2xl font-bold text-zinc-900 dark:text-white">
                Modern File Upload Test
            </h1>
            <p class="mb-6 text-zinc-600 dark:text-zinc-400">
                Test the modern chunked file upload component matching the uploaded image design.
            </p>

            {{-- Success Message --}}
            @if (session('success'))
                <div
                    class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit="submit">
                <div class="space-y-6">
                    {{-- Document Title --}}
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Document Title
                        </label>
                        <input type="text" wire:model="data.title" placeholder="Enter document title..."
                            class="w-full rounded-lg border border-zinc-300 px-4 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" />
                    </div>

                    {{-- Modal File Upload (Matching uploaded image design) --}}
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Upload Files (Modal)
                        </label>
                        <livewire:chunked-file-upload wire:key="modal-upload" :modal-mode="true" :allow-url-import="true"
                            :multiple="true" :accepted-file-types="['image/jpeg', 'image/png', 'application/pdf', 'video/mp4']" :max-files="10" :max-file-size="52428800" />
                    </div>

                    {{-- Inline File Upload Example --}}
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Upload Files (Inline)
                        </label>
                        <livewire:chunked-file-upload wire:key="inline-upload" :modal-mode="false" :allow-url-import="false"
                            :multiple="true" :accepted-file-types="['image/jpeg', 'image/png', 'application/pdf', 'video/*']" :max-files="5" :max-file-size="10485760" />
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4">
                    <button type="button"
                        class="px-4 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                        wire:click="mount">
                        Reset
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Debug Info -->
    <div class="mt-6 rounded-lg bg-zinc-100 p-4 dark:bg-zinc-700">
        <h3 class="mb-2 font-semibold text-zinc-900 dark:text-white">Debug Info</h3>
        <pre class="overflow-auto text-xs text-zinc-700 dark:text-zinc-300">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
