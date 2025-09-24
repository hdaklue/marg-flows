<div class="mx-auto flex h-screen w-full max-w-4xl flex-col rounded-lg bg-white/95 shadow-xl dark:bg-zinc-900/95">
    {{-- Modal Header --}}
    <div class="flex flex-shrink-0 items-center justify-between border-b border-zinc-200 p-6 dark:border-zinc-700">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                Document Version History
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $loadedVersions->count() }} version{{ $loadedVersions->count() !== 1 ? 's' : '' }} loaded
                @if($hasMoreVersions), more available @endif
            </p>
        </div>

        <button type="button" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
            wire:click="$dispatch('closeModal')">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    {{-- Modal Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        @if ($loadedVersions->count() > 0)
            <div class="space-y-4">
                @foreach ($loadedVersions as $version)
                    <livewire:document-version-item 
                        :version-id="$version->id" 
                        :document-id="$documentId"
                        :created-at="$version->created_at->toISOString()" 
                        :is-current-version="$version->id === $currentEditingVersion"
                        :creator-name="$version->creator?->name"
                        :key="$version->id" 
                    />
                @endforeach

                {{-- Infinite Scroll Trigger --}}
                @if ($hasMoreVersions)
                    <div x-intersect="$wire.loadMoreVersions()" class="flex justify-center py-4">
                        @if ($isLoading)
                            {{-- Loading Skeleton --}}
                            <div class="w-full animate-pulse">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="mb-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="h-4 w-16 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                <div class="h-4 w-12 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                            </div>
                                            <div class="h-4 w-20 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                        </div>
                                        <div class="mt-2 h-3 w-32 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                    </div>
                                @endfor
                            </div>
                        @else
                            {{-- Load More Indicator --}}
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                Scroll to load more versions...
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="py-12 text-center">
                <div class="text-zinc-400 dark:text-zinc-500">
                    <svg class="mx-auto mb-4 h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <h3 class="mb-2 text-lg font-medium text-zinc-900 dark:text-zinc-100">No versions yet</h3>
                    <p class="text-sm">Start editing the document to create your first version.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Footer --}}
    <div class="flex items-center justify-between border-t border-zinc-200 p-6 dark:border-zinc-700">
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
            Versions are automatically saved as you edit
        </div>

        <button type="button"
            class="px-4 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100"
            wire:click="$dispatch('closeModal')">
            Close
        </button>
    </div>
</div>
