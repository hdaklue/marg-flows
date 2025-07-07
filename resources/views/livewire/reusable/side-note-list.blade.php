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
        <div
            class="text-2xs absolute -right-1/2 -top-1/2 h-4 w-4 rounded-full bg-yellow-300 p-0.5 text-center text-yellow-800 dark:bg-yellow-600 dark:text-yellow-800">
            {{ $this->notes->count() }}
        </div>
        <a @click.prevent="show = !show" x-tooltip="tip">
            <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
        </a>
        <div class="absolute -top-[370px] right-0 h-[350px] w-[300px] rounded-lg border border-gray-200 bg-gray-50 p-2 shadow dark:border-gray-700 dark:bg-gray-800"
            x-show="show" x-cloak x-tansition @click.outside="show = false" @keyup.escape.window="show = false">

            <div class="flex flex-col justify-between w-full h-full cursor-normal">
                <div class="flex flex-col h-full devide-x">
                    @foreach ($this->notes as $note)
                        <p
                            class="py-1 text-sm text-gray-800 border-b border-gray-100 dark:border-gray-700 dark:text-gray-200">
                            {{ $note->content }}
                        </p>
                        <p
                            class="py-1 text-sm text-gray-800 border-b border-gray-100 dark:border-gray-700 dark:text-gray-200">
                            {{ $note->content }}
                        </p>
                    @endforeach
                </div>
                <div class="flex w-full h-8 grow-0 justify-self-end">
                    {{-- <input type="text" x-model="cotent" placeholder="enter note"
                        class="w-full text-xs bg-transperant dark:text-gray-300"> --}}
                    <x-filament::input.wrapper class="w-full">
                        <x-filament::input type="text" @keydown.enter="addNote() " x-model="content"
                            class="w-full text-2xs" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>
</div>
