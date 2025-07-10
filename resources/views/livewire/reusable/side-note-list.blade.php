<div x-data="{
    tip: 'Side note',
    show: false,
    content: '',
    addNote() {
        if (this.content != '') {
            $wire.addNote(this.content);
            this.content = '';
        }
    },
}"
    class="fixed p-2 transition-all rounded-full bottom-4 right-4 bg-slate-400 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500">
    <div class="relative">
        <div class="relative cursor-pointer">

            <a @click.prevent="show = !show" x-tooltip="tip" class="relative w-5 h-5">
                <div
                    class="text-2xs absolute -right-2/3 -top-1/2 h-4 w-4 rounded-full bg-yellow-300 p-0.5 text-center text-yellow-800 dark:bg-yellow-600 dark:text-yellow-800">
                    {{ $this->notes->count() }}
                </div>
                <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
            </a>
        </div>
        <div class="absolute -top-[370px] right-0 h-[350px] w-[300px] rounded-lg border border-gray-200 bg-gray-50 p-2 shadow dark:border-gray-700 dark:bg-gray-800"
            x-show="show" x-cloak x-tansition @click.outside="show = false" @keyup.escape.window="show = false">

            <div class="flex flex-col justify-between w-full h-full cursor-normal gap-y-1">
                <header
                    class="flex items-center justify-between p-1 text-xs font-semibold text-gray-800 dark:text-gray-500">
                    <div>
                        <p> Side Notes</p>
                        <p class="font-normal text-2xs">Only you can see this notes</p>
                    </div>
                    <div>
                        <x-filament::loading-indicator class="w-4 h-4" wire:loading />

                    </div>
                </header>
                <div class="flex flex-col items-start h-full max-h-full py-1 overflow-y-auto gap-y-1">
                    @foreach ($this->notes as $note)
                        <div
                            class="relative flex flex-col w-full px-2 py-1 border border-gray-100 rounded-lg gap-y-1 dark:border-gray-800 dark:bg-gray-900/50">
                            <p class="text-xs text-gray-800 dark:text-gray-200">
                                {{ $note->content }}
                            </p>
                            <div class="flex items-center justify-between">
                                <p class="text-gray-800 text-2xs justify-self-end text-end dark:text-gray-400">
                                    {{ toUserDateTime($note->created_at, filamentUser()) }}
                                </p>
                                <button type="button" wire:loading.attr="disabled"
                                    class="p-1 text-gray-800 transition-colors rounded-lg hover:bg-red-400 hover:text-red-900 dark:text-gray-400 dark:hover:bg-red-300 dark:hover:text-red-800"
                                    wire:click="deleteNote('{{ $note->getKey() }}')">
                                    <x-heroicon-o-trash class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex w-full h-8 grow-0 justify-self-end">
                    {{-- <input type="text" x-model="cotent" placeholder="enter note"
                        class="w-full text-xs bg-transperant dark:text-gray-300"> --}}
                    <x-filament::input.wrapper class="w-full" autofocus>
                        <x-filament::input autofocus type="text" @keydown.enter="addNote() " x-model="content"
                            class="w-full text-2xs" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>
</div>
