<div
    class="{{ $isCurrentVersion ? 'border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-950/20' : 'border-transparent' }} group rounded-lg border p-3 transition-colors duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">

    <div class="flex items-center justify-between">
        {{-- Version Info --}}
        <div class="flex items-center gap-3">
            {{-- Version ID (last 6 chars) --}}
            <div class="font-mono text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $this->shortVersionId }}
            </div>

            {{-- Current Version Badge --}}
            @if ($isCurrentVersion)
                <span
                    class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">
                    Current
                </span>
            @endif
        </div>

        {{-- Date and Actions --}}
        <div class="flex items-center gap-3">
            {{-- Date --}}
            <span class="text-sm text-zinc-500 dark:text-zinc-400"
                title="{{ \Carbon\Carbon::parse($createdAt)->format('M j, Y \a\t g:i A') }}">
                {{ $this->relativeTime }}
            </span>

            {{-- Actions --}}
            <div class="flex items-center gap-1">
                @unless ($isCurrentVersion)
                    {{-- Preview Button --}}
                    <button wire:click="openPreview" 
                        class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-zinc-500 bg-transparent border border-transparent rounded-lg hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-300 dark:focus:ring-zinc-400"
                        title="Preview">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    
                    {{-- Apply Button --}}
                    {{ $this->applyAction }}
                @endunless
            </div>
        </div>
    </div>

    {{-- Creator Name --}}
    @if ($creatorName)
        <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
            Created by {{ $creatorName }}
        </div>
    @endif
    <x-filament-actions::modals />
</div>
