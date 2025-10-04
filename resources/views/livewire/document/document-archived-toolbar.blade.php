<!-- Archived Document Toolbar -->
<div x-cloak x-bind:style="isSticky ? `top: 0px;` : ''"
    :class="{
        'fixed left-0 right-0 z-20 bg-amber-50/90 dark:bg-amber-900/20 backdrop-blur-sm py-2 border-y border-amber-200 dark:border-amber-800': isSticky,
        'mb-4': !isSticky,
        'flex items-center justify-center space-x-3 text-sm transition-all duration-150 ease-out': true
    }">

    <div
        :class="{
            'w-full md:max-w-5xl': isSticky,
            'w-full md:max-w-3/4': !isSticky,
            'flex items-center justify-center space-x-3': true
        }">

        <!-- Archive Icon -->
        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30">
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
        </div>

        <!-- Archive Message -->
        <div class="flex items-center space-x-2">
            <div class="h-2 w-2 rounded-full bg-amber-500 dark:bg-amber-400"></div>
            <span class="font-medium text-amber-800 dark:text-amber-200">
                {{ __('document.archived.message') }}
            </span>
        </div>

        <!-- Archive Info -->
        <div class="hidden sm:flex items-center text-amber-700 dark:text-amber-300">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm">{{ __('document.archived.read_only') }}</span>
        </div>

        <!-- Restore Button (if user has permission) -->
        @if(filamentUser()->can('restore', $this->document))
            <button
                wire:click="restoreDocument"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-amber-700 bg-amber-100 border border-amber-300 rounded-md hover:bg-amber-200 hover:border-amber-400 dark:text-amber-200 dark:bg-amber-800/30 dark:border-amber-700 dark:hover:bg-amber-800/50 dark:hover:border-amber-600 transition-colors duration-200"
                x-tooltip="'{{ __('document.archived.restore_tooltip') }}'">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ __('document.archived.restore') }}
            </button>
        @endif

        <!-- User Avatars -->
        <div class="flex items-center ml-4">
            <x-user-avatar-stack :users="$this->participantsArrayComputed" size='2xs' />
        </div>
    </div>
</div>
