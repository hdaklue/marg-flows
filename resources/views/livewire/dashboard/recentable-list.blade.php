<div class="flex w-full flex-col space-y-1">
    @foreach ($this->recents as $recent)
        <div class="w-full">
            <a href="{{ $recent['url'] }}" class="block rounded-md bg-zinc-200/20 px-2.5 py-2 dark:bg-zinc-800/30">
                <div class="grid grid-cols-2">
                    <div class="text-sm font-medium">
                        {{ $recent['title'] }}
                    </div>
                    <div class="rounded text-sm capitalize text-zinc-500/70">
                        {{ $recent['type'] }}
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>
