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
    class="fixed bottom-4 right-4 rounded-full bg-slate-400 p-2 transition-all hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500">
    <div class="relative">
        <div
            class="text-2xs absolute -right-1/2 -top-1/2 h-4 w-4 rounded-full bg-yellow-300 p-0.5 text-center text-yellow-800 dark:bg-yellow-600 dark:text-yellow-800">
            {{ $this->notes->count() }}
        </div>
        <a @click.prevent="show = !show" x-tooltip="tip">
            <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
        </a>
        <div class="absolute -top-[370px] right-0 h-[350px] w-[300px] rounded-lg border border-gray-200 bg-gray-50 p-2 shadow dark:border-gray-700 dark:bg-gray-800"
            x-show="show" x-cloak x-tansition @click.outside="show = false" @keyup.escape.window="show = false">

            <div class="cursor-normal flex h-full w-full flex-col justify-between gap-y-1">
                <div class="flex max-h-full flex-col gap-y-1 overflow-y-auto py-1">
                    @foreach ($this->notes as $note)
                        <div
                            class="relative flex flex-col gap-y-2 rounded-lg border border-gray-100 p-2 dark:border-gray-700">
                            <p class="text-sm text-gray-800 dark:text-gray-200">
                                {{ $note->content }}
                            </p>
                            <div class="flex items-center justify-between">
                                <p class="text-2xs justify-self-end text-end text-gray-800 dark:text-gray-400">
                                    {{ toUserDate($note->created_at, filamentUser()) }}
                                </p>
                                <button type="button"
                                    class="rounded-lg p-1 text-gray-800 transition-colors hover:bg-red-400 hover:text-red-900 dark:text-gray-400 dark:hover:bg-red-300 dark:hover:text-red-800"
                                    wire:click="deleteNote('{{ $note->getKey() }}')">
                                    <x-heroicon-o-trash class="h-3 w-3" />
                                </button>
                            </div>


                        </div>
                        {{-- <p
                            class="py-1 text-sm text-gray-800 border-b border-gray-100 dark:border-gray-700 dark:text-gray-200">
                            {{ $note->content }}
                        </p> --}}
                    @endforeach
                </div>
                <div class="flex h-8 w-full grow-0 justify-self-end">
                    {{-- <input type="text" x-model="cotent" placeholder="enter note"
                        class="w-full text-xs bg-transperant dark:text-gray-300"> --}}
                    <x-filament::input.wrapper class="w-full">
                        <x-filament::input type="text" @keydown.enter="addNote() " x-model="content"
                            class="text-2xs w-full" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>
</div>
