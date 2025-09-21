<div
    class="{{ $isCurrentVersion ? 'border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-950/20' : 'border-transparent' }} group rounded-lg border p-3 transition-colors duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">

    <div class="flex items-center justify-between">
        {{-- Version Info --}}
        <div class="flex items-center gap-3">
            {{-- Version ID --}}
            <div class="font-mono text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $versionId }}
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
            <div class="flex items-center gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                @unless ($isCurrentVersion)
                    {{ $this->previewAction }}
                    {{ $this->applyAction }}
                @endunless
            </div>
        </div>
    </div>
    <x-filament-actions::modals />
</div>
