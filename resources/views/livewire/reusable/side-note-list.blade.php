<div x-data="{
    show: false,
    content: '',
    addNote() {
        if (this.content != '') {
            $wire.addNote(this.content);
            this.content = '';
        }
    },
}" class="fixed bottom-4 end-4 z-50" @keydown.window.alt.s.prevent="show = true"
    @keydown.window.ctrl.s.prevent="show = true">
    <div class="relative">
        <div class="relative cursor-pointer rounded-full bg-zinc-300 p-2 transition-all hover:bg-zinc-300 dark:bg-zinc-600 dark:hover:bg-zinc-500"x-show="!show"
            @click.prevent="show = !show" x-transition:enter="transition ease-out duration-50"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-50" x-transition:leave-start="opacity-0 scale-100"
            x-transition:leave-end="opacity-100 scale-90">
            <a x-tooltip.arrowless.raw="{{ __('ui.components.side_notes.tooltip') }}" class="relative h-5 w-5">
                <div
                    class="text-2xs absolute -end-2/3 -top-1/2 h-4 w-4 rounded-full bg-amber-200 p-0.5 text-center text-amber-800 dark:bg-amber-200 dark:text-amber-700">
                    {{ $this->notes->count() }}
                </div>
                <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
            </a>
        </div>
        <div class="absolute bottom-0 end-0 h-[350px] w-[300px] rounded-lg border border-zinc-200 bg-zinc-50 p-2 shadow dark:border-zinc-700 dark:bg-zinc-800"
            x-show="show" x-cloak x-tansition x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90" @click.outside="show = false"
            @keyup.escape.window="show = false" x-init="$watch('show', value => value && $nextTick(() => $refs.noteInput.focus()))">

            <div class="cursor-normal flex h-full w-full flex-col justify-between gap-y-1">
                <header
                    class="flex items-center justify-between p-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                    <div>
                        <p>{{ __('ui.components.side_notes.title') }}</p>
                        <p class="text-2xs font-normal">{{ __('ui.components.side_notes.subtitle') }}</p>
                    </div>
                    <div class="flex">

                        <x-filament::loading-indicator class="h-4 w-4" wire:loading />
                        <x-filament::icon-button icon="heroicon-o-x-mark" size="xs" color="zinc"
                            @click="show = false" />

                    </div>
                </header>
                <div class="flex h-full max-h-full flex-col items-start gap-y-1 overflow-y-auto py-1">
                    @foreach ($this->notes as $note)
                        <div
                            class="relative flex w-full flex-col gap-y-1 rounded-lg border border-zinc-100 bg-zinc-100 px-2 py-1 dark:border-zinc-800 dark:bg-zinc-900/50">
                            <p class="prose-sm prose dark:prose-invert text-zinc-800 dark:text-zinc-200">
                                {!! $note->content !!}
                            </p>
                            <div class="flex items-center justify-between">
                                <p class="text-2xs justify-self-end text-end text-zinc-800 dark:text-zinc-400">
                                    {{ toUserDiffForHuman($note->created_at, filamentUser()) }}
                                </p>
                                <button type="button" wire:loading.attr="disabled"
                                    class="rounded-lg p-1 text-zinc-800 transition-colors hover:bg-red-400 hover:text-red-900 dark:text-zinc-400 dark:hover:bg-red-300 dark:hover:text-red-800"
                                    wire:click="deleteNote('{{ $note->getKey() }}')">
                                    <x-heroicon-o-trash class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex h-8 w-full grow-0 justify-self-end">
                    {{-- <input type="text" x-model="cotent" placeholder="enter note"
                        class="w-full text-xs bg-transperant dark:text-zinc-300"> --}}
                    <x-filament::input.wrapper class="w-full !text-sm">
                        <x-filament::input x-ref="noteInput" type="text" @keydown.enter="addNote() "
                            x-model="content" class="!py-1.5 !pe-6 !ps-2 !text-sm"
                            placeholder="{{ __('ui.components.side_notes.placeholder') }}" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>
</div>
