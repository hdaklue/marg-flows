<div>
    <!-- Simple upload progress bar during wire:loading -->
    <div wire:loading wire:target="addVoiceNote"
        class="w-full h-1 mb-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
        <div class="h-full rounded-full animate-pulse bg-amber-500"></div>
    </div>
    <!-- Uploaded Voice Notes Players -->
    @if (!empty($this->getNotesUrls))
        <div class="mb-3 space-y-2">
            @foreach ($this->getNotesUrls as $index => $url)
                <div wire:ignore class="flex items-center gap-2" wire:key="voice-note-{{ md5($url) }}">
                    <div class="flex-1">
                        <x-audio-player :audio-url="$url" size="sm" :outlined="false" :player-key="'voice-note-' . $index" />
                    </div>
                    <button wire:click="removeVoiceNote({{ $index }})"
                        class="flex items-center justify-center w-6 h-6 text-red-500 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    @if ($this->canAcceptNotes)
        <!-- Voice Recorder Interface (Livewire) -->
        <livewire:reusable.voice-recorder-component outlined wire:key="{{ $recorderKey }}" wire:ignore
            :maxDuration="$maxDurationInSeconds" />
    @else
        <!-- Max voice notes reached -->
        {{-- <div
            class="flex items-center gap-2 p-3 border rounded-lg border-amber-300 bg-amber-50 dark:border-amber-600 dark:bg-amber-900/20">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"></path>
            </svg>
            <span class="text-sm text-amber-700 dark:text-amber-300">Maximum {{ $this->maxNotes }} voice notes reached.
                Remove one to
                add more.</span>
        </div> --}}
    @endif
</div>
