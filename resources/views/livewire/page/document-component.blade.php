<div wire:ignore x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('documentEditor') }}"
    x-data="documentEditor(@js($content), '{{ route('editorjs.uploade-image') }}', @js($canEdit), $wire.saveDocument, 25, '{{ $this->page->updated_at->toISOString() }}', @js($this->getToolsConfig()))" class="w-full">

    <!-- Intersection Observer Target -->
    <div x-intersect:leave.margin.-80px="isSticky = true" x-intersect:enter.margin.-80px="isSticky = false" class="h-4">
    </div>

    <!-- Save Status Indicator -->
    <div x-cloak x-bind:style="isSticky ? `top: ${topbarHeight}px;` : ''"
        :class="{
            'fixed left-0 right-0 z-40 bg-white/80 dark:bg-zinc-900  py-2 border-y border-zinc-200 dark:border-zinc-700': isSticky,
            'mb-3': !isSticky,
            'flex items-center justify-center space-x-2 text-xs transition-all duration-150 ease-out': true
        }">

        <div
            :class="{
                'w-full md:max-w-5xl': isSticky,
                'w-full md:max-w-3/4': !isSticky,
                'flex items-center justify-center space-x-2': true
            }">


            <!-- Save status with dot -->
            <div class="flex items-center space-x-1.5">
                <div class="h-1.5 w-1.5 rounded-full"
                    x-bind:class="{
                        'bg-amber-400': currentStatus.isDirty && !currentStatus.isSaving,
                        'bg-blue-400 animate-pulse': currentStatus.isSaving,
                        'bg-emerald-400': currentStatus.saveStatus === 'success',
                        'bg-rose-400': currentStatus.saveStatus === 'error',
                        'bg-zinc-400': !currentStatus.isDirty && !currentStatus.isSaving && !currentStatus.saveStatus
                    }">
                </div>
                <span class="w-28 text-left text-zinc-600 dark:text-zinc-400" x-text="currentStatus.statusText"></span>
            </div>

            <!-- Last saved time -->
            <span class="text-zinc-500 dark:text-zinc-500">
                • <span x-text="formatLastSaved()"></span>
            </span>

            <!-- Manual Save Button -->
            <button @click="saveDocument()" :disabled="isSaving || isEditorBusy || !isDirty"
                :class="{
                    'opacity-50 cursor-not-allowed border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400': isSaving ||
                        isEditorBusy || !isDirty,
                    'border-sky-500 text-sky-600 dark:border-sky-400 dark:text-sky-400 hover:border-sky-600 hover:text-sky-700 dark:hover:border-sky-300 dark:hover:text-sky-300':
                        !isSaving && !isEditorBusy && isDirty
                }"
                class="ml-3 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors">
                <span x-show="!isSaving" class="flex items-center space-x-1">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    <span>Save</span>
                </span>
                <span x-show="isSaving" class="flex items-center space-x-1">
                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
            <x-user-avatar-stack :users="$this->participantsArray" :roleableKey="$this->page->getKey()" :roleableType="$this->page->getMorphClass()" :canEdit="$this->userPermissions['canManageMembers']"
                size='2xs' />
        </div>

    </div>

    <div id="editor-wrap" wire:ignore @keydown.window.ctrl.k.prevent="saveDoument()" @keydown.meta.k="saveDocument()"
        class="prose:img:my-0 prose prose-sm prose-zinc mx-auto min-h-96 w-full dark:prose-invert lg:prose-xl prose-table:w-full">
    </div>

    <!-- Navigation Modal -->
    {{-- <div x-show="showNavigationModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @keydown.escape="closeNavigationModal()">

        <div class="w-full max-w-md mx-4 bg-white border shadow-2xl rounded-xl border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900"
            @click.stop>

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Unsaved Changes
                </h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    You have unsaved changes. What would you like to do?
                </p>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-700">
                <button @click="!isEditorBusy && (navigationActiveTab = 'save')" :disabled="isEditorBusy"
                    :class="[
                        navigationActiveTab === 'save' ?
                        'border-sky-500 text-sky-600 dark:text-sky-400' :
                        'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
                        isEditorBusy ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                    class="flex-1 px-4 py-3 text-sm font-medium transition-colors border-b-2">
                    💾 Save & Close
                </button>
                <button @click="!isEditorBusy && (navigationActiveTab = 'discard')" :disabled="isEditorBusy"
                    :class="[
                        navigationActiveTab === 'discard' ?
                        'border-rose-500 text-rose-600 dark:text-rose-400' :
                        'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
                        isEditorBusy ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                    class="flex-1 px-4 py-3 text-sm font-medium transition-colors border-b-2">
                    🗑️ Discard & Close
                </button>
                <button @click="navigationActiveTab = 'cancel'"
                    :class="navigationActiveTab === 'cancel'
                        ?
                        'border-zinc-500 text-zinc-600 dark:text-zinc-400' :
                        'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="flex-1 px-4 py-3 text-sm font-medium transition-colors border-b-2">
                    ❌ Cancel
                </button>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Save & Close Tab -->
                <div x-show="navigationActiveTab === 'save'" class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-sky-100 dark:bg-sky-900/50">
                        <svg class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-base font-medium text-zinc-900 dark:text-white">Save Changes & Close</h4>
                    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                        Your changes will be saved before navigating to the new page.
                    </p>
                    <button @click="saveAndClose()"
                        class="w-full rounded-lg bg-sky-600 px-4 py-2.5 font-medium text-white transition-colors hover:bg-sky-700">
                        Save & Close
                    </button>
                </div>

                <!-- Discard & Close Tab -->
                <div x-show="navigationActiveTab === 'discard'" class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-rose-100 dark:bg-rose-900/50">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-base font-medium text-zinc-900 dark:text-white">Discard Changes & Close</h4>
                    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                        Your changes will be lost permanently. This action cannot be undone.
                    </p>
                    <button @click="discardAndClose()"
                        class="w-full rounded-lg bg-rose-600 px-4 py-2.5 font-medium text-white transition-colors hover:bg-rose-700">
                        Discard & Close
                    </button>
                </div>

                <!-- Cancel Tab -->
                <div x-show="navigationActiveTab === 'cancel'" class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <svg class="w-6 h-6 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-base font-medium text-zinc-900 dark:text-white"
                        x-text="isEditorBusy ? 'Wait for Processing' : 'Stay on Current Page'"></h4>
                    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400"
                        x-text="isEditorBusy
                           ? 'The editor is processing. Please wait or risk losing data.'
                           : 'Continue editing your document. Navigation will be cancelled.'">
                    </p>
                    <button @click="closeNavigationModal()"
                        class="w-full rounded-lg bg-zinc-600 px-4 py-2.5 font-medium text-white transition-colors hover:bg-zinc-700"
                        x-text="isEditorBusy ? 'Wait Here' : 'Stay Here'">
                    </button>
                </div>
            </div>
        </div>
    </div> --}}
    <livewire:role.manage-members-modal />
    @vite(['resources/css/components/editorjs/index.css', 'resources/css/components/editorjs/comment-tune.css', 'resources/css/components/document/document.css'])
</div>
