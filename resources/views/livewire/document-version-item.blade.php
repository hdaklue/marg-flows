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
                    {{ $this->previewAction }}
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
