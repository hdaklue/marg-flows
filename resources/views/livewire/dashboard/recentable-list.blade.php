<div class="flex flex-col w-full space-y-1 divide-y divide-zinc-400/20 dark:divide-zinc-800/25">
    @foreach ($this->recents as $recent)
        <div class="w-full">
            <a href="{{ $recent['url'] }}" class="block rounded-md px-2.5 py-2">
                <div class="grid grid-cols-2">
                    <div class="text-sm font-medium">
                        {{ $recent['title'] }}
                    </div>
                    <div class="flex justify-end">
                        <span
                            class="bg-{{ $recent['color'] }}-50 text-{{ $recent['color'] }}-500 ring-{{ $recent['color'] }}-200 dark:bg-{{ $recent['color'] }}-800 dark:text-{{ $recent['color'] }}-500 dark:ring-{{ $recent['color'] }}-600 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ring-inset">
                            {{ $recent['type'] }}
                        </span>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>
