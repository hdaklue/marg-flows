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
}" class="fixed bottom-4 right-4">
    <div class="relative">
        <div class="relative cursor-pointer rounded-full bg-slate-400 p-2 transition-all hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500"x-show="!show"
            @click.prevent="show = !show" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-0 scale-100"
            x-transition:leave-end="opacity-100 scale-90">
            <a x-tooltip="tip" class="relative h-5 w-5">
                <div
                    class="text-2xs absolute -right-2/3 -top-1/2 h-4 w-4 rounded-full bg-yellow-300 p-0.5 text-center text-yellow-800 dark:bg-yellow-600 dark:text-yellow-800">
                    {{ $this->notes->count() }}
                </div>
                <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
            </a>
        </div>
        <div class="absolute bottom-0 right-0 h-[350px] w-[300px] rounded-lg border border-gray-200 bg-gray-50 p-2 shadow dark:border-gray-700 dark:bg-gray-800"
            x-show="show" x-cloak x-tansition x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90" @click.outside="show = false"
            @keyup.escape.window="show = false">

            <div class="cursor-normal flex h-full w-full flex-col justify-between gap-y-1">
                <header
                    class="flex items-center justify-between p-1 text-xs font-semibold text-gray-800 dark:text-gray-500">
                    <div>
                        <p> Side Notes</p>
                        <p class="text-2xs font-normal">Only you can see this notes</p>
                    </div>
                    <div class="flex">

                        <x-filament::loading-indicator class="h-4 w-4" wire:loading />
                        <x-filament::icon-button icon="heroicon-o-x-mark" size="xs" color="gray"
                            @click="show = false" />

                    </div>
                </header>
                <div class="flex h-full max-h-full flex-col items-start gap-y-1 overflow-y-auto py-1">
                    @foreach ($this->notes as $note)
                        <div
                            class="relative flex w-full flex-col gap-y-1 rounded-lg border border-gray-100 px-2 py-1 dark:border-gray-800 dark:bg-gray-900/50">
                            <p class="text-xs text-gray-800 dark:text-gray-200">
                                {{ $note->content }}
                            </p>
                            <div class="flex items-center justify-between">
                                <p class="text-2xs justify-self-end text-end text-gray-800 dark:text-gray-400">
                                    {{ toUserDateTime($note->created_at, filamentUser()) }}
                                </p>
                                <button type="button" wire:loading.attr="disabled"
                                    class="rounded-lg p-1 text-gray-800 transition-colors hover:bg-red-400 hover:text-red-900 dark:text-gray-400 dark:hover:bg-red-300 dark:hover:text-red-800"
                                    wire:click="deleteNote('{{ $note->getKey() }}')">
                                    <x-heroicon-o-trash class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex h-8 w-full grow-0 justify-self-end">
                    {{-- <input type="text" x-model="cotent" placeholder="enter note"
                        class="w-full text-xs bg-transperant dark:text-gray-300"> --}}
                    <x-filament::input.wrapper class="w-full !text-sm" autofocus>
                        <x-filament::input autofocus type="text" @keydown.enter="addNote() " x-model="content"
                            class="!py-1.5 !pe-6 !ps-2 !text-sm" placeholder="write your note .. and hit Enter" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>
</div>
