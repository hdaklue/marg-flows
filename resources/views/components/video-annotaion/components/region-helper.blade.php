<div x-show="showRegionToolbar" x-cloak x-ref="regionToolbar"
    class="absolute z-50 p-4 transition-opacity duration-200 transform -translate-x-1/2 border shadow-lg cursor-move select-none bottom-28 left-1/2 min-w-80 rounded-2xl border-zinc-200/50 bg-white/95 opacity-30 backdrop-blur-md hover:opacity-100 dark:border-zinc-700/50 dark:bg-zinc-900/95 dark:shadow-zinc-900/20"
    x-data="{ isDragging: false, dragStarted: false }" @touchstart="isDragging = false; dragStarted = false"
    @mousedown="isDragging = false; dragStarted = false" style="touch-action: none;" :class="{ 'opacity-70': isDragging }"
    x-init="initRegionToolbarDrag($refs.regionToolbar)" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 transform translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 transform translate-y-4 scale-95">

    <!-- Frame Navigation -->
    <div class="flex items-center justify-between mb-4">
        <!-- Left side: Previous -->
        <div class="flex items-center space-x-1">
            <button @click="jumpFrames(-10)"
                class="flex items-center justify-center w-10 h-10 transition-all rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200 hover:text-zinc-900 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                style="touch-action: manipulation; -webkit-tap-highlight-color: transparent;"
                title="Previous 10 frames">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>

            <button @click="goToPreviousFrame()"
                class="flex items-center justify-center w-10 h-10 transition-all rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200 hover:text-zinc-900 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                style="touch-action: manipulation; -webkit-tap-highlight-color: transparent;" title="Previous frame">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </div>

        <!-- Center: Frame Range Display -->
        <div class="text-center">
            <div class="text-sm font-mono text-zinc-700 dark:text-zinc-300"
                x-text="regionCreationStart && regionCreationEnd ? 
                    'F' + getFrameNumber(regionCreationStart.time) + ' → F' + getFrameNumber(regionCreationEnd.time) : 
                    'Frame ' + (currentFrameNumber || getFrameNumber(currentTime || 0))">
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="(frameRate || 30) + ' fps'"></div>
            <div class="mt-1 text-xs font-mono text-zinc-400 dark:text-zinc-500"
                x-text="regionCreationStart && regionCreationEnd ? 
                    formatTime(regionCreationStart.time) + ' → ' + formatTime(regionCreationEnd.time) : 
                    formatTime(currentTime || 0)">
            </div>
        </div>

        <!-- Right side: Next -->
        <div class="flex items-center space-x-1">
            <button @click="goToNextFrame()"
                class="flex items-center justify-center w-10 h-10 transition-all rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200 hover:text-zinc-900 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                style="touch-action: manipulation; -webkit-tap-highlight-color: transparent;" title="Next frame">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <button @click="jumpFrames(10)"
                class="flex items-center justify-center w-10 h-10 transition-all rounded-xl bg-zinc-100 text-zinc-600 hover:bg-zinc-200 hover:text-zinc-900 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                style="touch-action: manipulation; -webkit-tap-highlight-color: transparent;" title="Next 10 frames">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex space-x-2">
        <button @click="finishRegionCreation()"
            class="flex-1 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition-all hover:bg-sky-700 dark:bg-sky-600 dark:hover:bg-sky-700"
            style="touch-action: manipulation; -webkit-tap-highlight-color: transparent;">
            Finish
        </button>

        <button @click="cancelRegionCreation()"
            class="flex-1 rounded-xl bg-zinc-100 px-4 py-2.5 text-sm font-medium text-zinc-700 transition-all hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
            style="touch-action: manipulation; -webkit-tap-highlight-color: transparent;">
            Cancel
        </button>
    </div>
</div>
