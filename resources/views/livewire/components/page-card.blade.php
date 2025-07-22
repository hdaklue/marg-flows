<div>
    <div x-data="{
        editing: false,
        loading: false,
        success: false,
        error: false,
        showMenu: false,
        menuX: 0,
        menuY: 0,
        isTouchDevice: 'ontouchstart' in window,
        longPressTimer: null,
        title: '{{ $this->page->name }}',
        originalTitle: '{{ $this->page->name }}',
        canEdit: true, // TODO: replace with actual permission check
    
        startEdit() {
            if (!this.canEdit) return;
            this.editing = true;
            this.$nextTick(() => this.$refs.input.focus());
        },
    
        handleTitleClick(event) {
            // Prevent event bubbling to card click
            event.stopPropagation();
    
            if (!this.isTouchDevice) {
                // Desktop: direct edit on title click
                this.startEdit();
            } else {
                // Mobile: open page on title tap
                $wire.openPage();
            }
        },
    
        cancelEdit() {
            this.title = this.originalTitle;
            this.editing = false;
        },
    
        async saveEdit() {
            if (this.title.trim() === '') return;
            if (this.title === this.originalTitle) {
                this.editing = false;
                return;
            }
    
            this.loading = true;
            this.success = false;
            this.error = false;
    
            try {
                await $wire.updateTitle(this.title);
                this.originalTitle = this.title;
                this.success = true;
                setTimeout(() => this.success = false, 2000);
            } catch (error) {
                this.title = this.originalTitle;
                this.error = true;
                setTimeout(() => this.error = false, 2000);
                console.error('Failed to update title:', error);
            }
            this.loading = false;
            this.editing = false;
        },
    
        showContextMenu(event) {
            event.preventDefault();
            if (this.isTouchDevice) {
                // Touch device - show modal
                this.showMenu = true;
            } else {
                // Desktop - show anchored menu
                this.menuX = event.clientX;
                this.menuY = event.clientY;
                this.showMenu = true;
                // Close menu when clicking elsewhere
                this.$nextTick(() => {
                    const closeMenu = (e) => {
                        if (!this.$refs.menu?.contains(e.target)) {
                            this.showMenu = false;
                            document.removeEventListener('click', closeMenu);
                        }
                    };
                    document.addEventListener('click', closeMenu);
                });
            }
        },
    
        handleTouchStart(event) {
            if (!this.isTouchDevice) return;
            this.longPressTimer = setTimeout(() => {
                this.showContextMenu(event);
                navigator.vibrate?.(50); // Haptic feedback if available
            }, 500);
        },
    
        handleTouchEnd() {
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
        },
    
        closeMenu() {
            this.showMenu = false;
        },
    
        handleSingleTap(event) {
            // Only for touch devices
            if (!this.isTouchDevice) return;
    
            // Don't trigger if we're in editing mode or menu is open
            if (this.editing || this.showMenu) return;
    
            // Don't trigger if long press timer is active (user might be long pressing)
            if (this.longPressTimer) return;
    
            // Open the page
            $wire.openPage();
        }
    }" @dblclick.prevent="$wire.openPage()" @click="handleSingleTap($event)"
        @contextmenu="showContextMenu($event)" @touchstart="handleTouchStart($event)" @touchend="handleTouchEnd()"
        @touchcancel="handleTouchEnd()"
        class="relative flex min-w-full select-none flex-col overflow-hidden rounded border border-zinc-300 transition-all hover:shadow-md hover:shadow-zinc-200 dark:border-zinc-700/50 dark:bg-zinc-900 dark:hover:bg-zinc-900/50 dark:hover:shadow-md dark:hover:shadow-black">

        <!-- Loading progress bar -->
        <div x-show="loading" x-cloak class="absolute left-0 top-0 h-1 w-full bg-zinc-700/30">
            <div class="h-full w-full animate-pulse bg-sky-500" style="transition: width 0.3s ease-out;">
            </div>
        </div>

        {{-- <button class="absolute z-10 p-1 end-1 top-1 dark:bg-zinc-900/30">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
        </svg>
    </button> --}}

        <div class="flex h-24 w-full items-center justify-center dark:text-zinc-700">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
        </div>

        <div class="flex h-auto flex-col justify-between gap-y-1 border-t border-zinc-700/30 p-2">
            <div class="relative h-8">
                <!-- Display Mode -->
                <div x-show="!editing" x-cloak
                    class="relative line-clamp-2 text-xs font-semibold transition-colors duration-200"
                    :class="{
                        'dark:text-zinc-300': !loading && !success && !error,
                        'text-green-500 dark:text-green-400': success,
                        'text-red-500 dark:text-red-400': error,
                        'opacity-50 dark:text-zinc-300': loading
                    }">
                    <span x-text="title"></span>

                    <!-- Loading indicator -->
                    <div x-show="loading" x-cloak class="absolute -right-1 -top-1">
                        <div class="h-3 w-3 animate-spin rounded-full border-2 border-sky-500 border-t-transparent">
                        </div>
                    </div>
                </div>

                <!-- Edit Mode -->
                <div x-show="editing" x-cloak>
                    <textarea x-ref="input" x-model="title" @keydown.enter.prevent="saveEdit()" @keydown.escape="cancelEdit()"
                        @blur="saveEdit()" rows="2"
                        class="w-full resize-none rounded border border-zinc-600 bg-transparent px-1 py-0.5 text-xs font-semibold focus:border-sky-500 focus:outline-none dark:text-zinc-300"></textarea>
                </div>
            </div>
            <p class="text-2xs self-baseline text-zinc-500">{{ $createdAt }}</p>
        </div>

        <!-- Desktop Context Menu (Anchored) -->
        <div x-show="showMenu && !isTouchDevice" x-cloak x-ref="menu"
            :style="`position: fixed; top: ${menuY}px; left: ${menuX}px; z-index: 1000;`"
            class="min-w-48 rounded-md border bg-white py-1 text-sm shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            <button @click="startEdit(); closeMenu()"
                class="flex w-full items-center px-3 py-2 text-xs hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Rename
            </button>
            <button @click="$wire.openPage(); closeMenu()"
                class="flex w-full items-center px-3 py-2 text-xs hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Open
            </button>
            <hr class="my-1 border-zinc-200 dark:border-zinc-700">
            <button @click="console.log('duplicate'); closeMenu()"
                class="flex w-full items-center px-3 py-2 text-xs hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Duplicate
            </button>
            {{-- <button @click="console.log('delete'); closeMenu()"
            class="flex items-center w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
        </button> --}}
        </div>

        <!-- Touch Modal Context Menu -->
        <div x-show="showMenu && isTouchDevice" x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4" @click.self="closeMenu()">
            <div class="pb-safe w-full max-w-sm rounded-t-xl bg-white dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b px-4 py-3 dark:border-zinc-700">
                    <h3 class="font-semibold dark:text-zinc-300">Page Options</h3>
                    <button @click="closeMenu()" class="rounded-full p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="py-2">
                    <button @click="startEdit(); closeMenu()"
                        class="flex w-full items-center px-4 py-3 text-left hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Rename
                    </button>
                    <button @click="$wire.openPage(); closeMenu()"
                        class="flex w-full items-center px-4 py-3 text-left hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Open
                    </button>
                    <button @click="console.log('duplicate'); closeMenu()"
                        class="flex w-full items-center px-4 py-3 text-left hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Duplicate
                    </button>
                    <button @click="console.log('delete'); closeMenu()"
                        class="flex w-full items-center px-4 py-3 text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="flex w-full justify-end py-2">
        <x-user-avatar-stack :users="$this->participantsArray" :roleableKey="$this->page->getKey()" :roleableType="$this->page->getMorphClass()" :canEdit="$this->userPermissions['canManageMembers']" size='2xs' />
    </div>
</div>
