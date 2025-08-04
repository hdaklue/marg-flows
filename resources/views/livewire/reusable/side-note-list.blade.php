<div x-data="{
    show: false,
    content: '',
    addNote() {
        if (this.content != '') {
            $wire.addNote(this.content);
            this.content = '';
        }
    },
}" class="fixed z-50 bottom-4 right-4" @keydown.window.alt.s.prevent="show = true"
    @keydown.window.ctrl.s.prevent="show = true">
    <div class="relative">
        <div class="relative p-2 transition-all rounded-full cursor-pointer bg-zinc-300 hover:bg-zinc-300 dark:bg-zinc-600 dark:hover:bg-zinc-500"x-show="!show"
            @click.prevent="show = !show" x-transition:enter="transition ease-out duration-50"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-50" x-transition:leave-start="opacity-0 scale-100"
            x-transition:leave-end="opacity-100 scale-90">
            <a x-tooltip.arrowless.raw="Sidenotes [Alt + S]" class="relative w-5 h-5">
                <div
                    class="text-2xs absolute -right-2/3 -top-1/2 h-4 w-4 rounded-full bg-amber-200 p-0.5 text-center text-amber-800 dark:bg-amber-200 dark:text-amber-700">
                    {{ $this->notes->count() }}
                </div>
                <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
            </a>
        </div>
        <div class="absolute bottom-0 right-0 h-[350px] w-[300px] rounded-lg border border-zinc-200 bg-zinc-50 p-2 shadow dark:border-zinc-700 dark:bg-zinc-800"
            x-show="show" x-cloak x-tansition x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90" @click.outside="show = false"
            @keyup.escape.window="show = false" x-init="$watch('show', value => value && $nextTick(() => $refs.noteInput.focus()))">

            <div class="flex flex-col justify-between w-full h-full cursor-normal gap-y-1">
                <header
                    class="flex items-center justify-between p-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                    <div>
                        <p> Side Notes</p>
                        <p class="font-normal text-2xs">Only you can see this notes</p>
                    </div>
                    <div class="flex">

                        <x-filament::loading-indicator class="w-4 h-4" wire:loading />
                        <x-filament::icon-button icon="heroicon-o-x-mark" size="xs" color="zinc"
                            @click="show = false" />

                    </div>
                </header>
                <div class="flex flex-col items-start h-full max-h-full py-1 overflow-y-auto gap-y-1">
                    @foreach ($this->notes as $note)
                        <div
                            class="relative flex flex-col w-full px-2 py-1 border rounded-lg gap-y-1 border-zinc-100 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900/50">
                            <p class="prose-sm prose text-zinc-800 dark:prose-invert dark:text-zinc-200">
                                {!! $note->content !!}
                            </p>
                            <div class="flex items-center justify-between">
                                <p class="text-2xs justify-self-end text-end text-zinc-800 dark:text-zinc-400">
                                    {{ toUserDateTime($note->created_at, filamentUser()) }}
                                </p>
                                <button type="button" wire:loading.attr="disabled"
                                    class="p-1 transition-colors rounded-lg text-zinc-800 hover:bg-red-400 hover:text-red-900 dark:text-zinc-400 dark:hover:bg-red-300 dark:hover:text-red-800"
                                    wire:click="deleteNote('{{ $note->getKey() }}')">
                                    <x-heroicon-o-trash class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex w-full h-8 grow-0 justify-self-end">
                    {{-- <input type="text" x-model="cotent" placeholder="enter note"
                        class="w-full text-xs bg-transperant dark:text-zinc-300"> --}}
                    <x-filament::input.wrapper class="w-full !text-sm">
                        <x-filament::input x-ref="noteInput" type="text" @keydown.enter="addNote() "
                            x-model="content" class="!py-1.5 !pe-6 !ps-2 !text-sm"
                            placeholder="write your note .. and hit Enter" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>
</div>
