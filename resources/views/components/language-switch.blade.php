<div class="relative" x-data="{ open: false }">
    <button 
        @click="open = !open"
        @click.outside="open = false"
        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white transition-colors"
    >
        <span class="w-6 h-6 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center text-xs font-semibold">
            {{ app()->getLocale() === 'ar' ? 'ع' : strtoupper(substr(app()->getLocale(), 0, 2)) }}
        </span>
    </button>

    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
        style="display: none;"
    >
        <div class="py-1">
            @foreach(config('app.available_locales') as $locale => $label)
                <a 
                    href="{{ route('language.switch', $locale) }}"
                    @click="open = false"
                    class="flex items-center w-full px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 {{ app()->getLocale() === $locale ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-200' }}"
                >
                    <div class="flex items-center justify-between w-full">
                        <span>{{ $label }}</span>
                        @if(app()->getLocale() === $locale)
                            <div class="w-2 h-2 bg-primary-600 dark:bg-primary-400 rounded-full"></div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>