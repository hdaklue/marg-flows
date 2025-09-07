<div x-data="chunkedFileUpload(@js($this->getComponentConfig()))" class="chunked-file-upload-wrapper" wire:ignore>

    {{-- Upload trigger button (when in modal mode) --}}
    @if ($modalMode)
        <button type="button" @click="openModal()"
            class="flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 cursor-pointer">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Upload Files
        </button>
    @endif

    {{-- Modal backdrop and dialog --}}
    <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 bg-zinc-800/90 backdrop-blur-sm"
        @click="closeModal()" style="display: none;">
        <div class="flex min-h-full items-center justify-center p-4">
            <div @click.stop x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-900" x-ref="modal">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-zinc-200 p-6 dark:border-zinc-700">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Upload files</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Select and upload the files of your
                            choice</p>
                    </div>
                    <button @click="closeModal()"
                        class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Modal Content --}}
                <div class="p-6">
                    {{-- Messages --}}
                    <div x-show="error" x-cloak x-transition
                        class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                        <div class="flex items-center">
                            <svg class="mr-2 h-4 w-4 text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-red-800 dark:text-red-200" x-text="error"></p>
                        </div>
                    </div>

                    <div x-show="success" x-cloak x-transition
                        class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <div class="flex items-center">
                            <svg class="mr-2 h-4 w-4 text-emerald-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-emerald-800 dark:text-emerald-200" x-text="success"></p>
                        </div>
                    </div>

                    {{-- Upload Area --}}
                    <div class="mb-6">
                        <label class="group block w-full cursor-pointer">
                            <div :class="isDragOver ? 'border-sky-500 bg-sky-50 dark:bg-sky-900/20' :
                                'border-zinc-300 dark:border-zinc-600'"
                                class="flex h-64 w-full flex-col items-center justify-center rounded-xl border-2 border-dashed transition-all duration-200 hover:border-sky-400 hover:bg-sky-50/50 group-hover:border-sky-400 dark:hover:bg-sky-900/10"
                                @drop.prevent="handleDrop($event)" @dragover.prevent="handleDragOver($event)"
                                @dragenter.prevent="handleDragOver($event)" @dragleave.prevent="handleDragLeave($event)"
                                x-ref="modalDropZone">
                                <div class="space-y-4 text-center">
                                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                        </path>
                                    </svg>

                                    <div class="space-y-2 text-center">
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            Choose a file or drag & drop it here.
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $this->acceptedTypesDisplay }}</p>
                                    </div>

                                    <span
                                        class="inline-block cursor-pointer rounded-lg bg-sky-600 px-6 py-3 text-sm font-medium text-white transition-all hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:bg-sky-700 dark:hover:bg-sky-600">
                                        Browse Files
                                    </span>
                                </div>
                            </div>

                            <input type="file" class="sr-only" x-ref="fileInput"
                                @if ($multiple) multiple @endif accept="{{ $this->acceptedTypes }}"
                                @change="handleFileSelect($event)" />
                        </label>
                    </div>

                    {{-- URL Import (if enabled) --}}
                    @if ($allowUrlImport)
                        <div class="mb-6">
                            <button @click="toggleUrlImport()"
                                class="flex items-center text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                    </path>
                                </svg>
                                Or import from URL
                            </button>

                            <div x-show="showUrlImport" x-cloak x-collapse class="mt-3">
                                <div class="flex gap-2">
                                    <input type="url" x-model="importUrl" x-ref="urlInput"
                                        placeholder="Enter file URL..."
                                        class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" />
                                    <button @click="importFromUrl()" :disabled="!importUrl.trim() || importingFromUrl"
                                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20 disabled:cursor-not-allowed disabled:opacity-50">
                                        <span x-show="!importingFromUrl" x-cloak>Import</span>
                                        <span x-show="importingFromUrl" x-cloak>Importing...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Uploading Files List --}}
                    <div x-show="uploadingFiles.length > 0" x-cloak class="space-y-3">
                        <template x-for="file in uploadingFiles" :key="file.id">
                            <div
                                class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-transparent transition-all duration-300"
                                    :style="`width: ${file.progress}%`"></div>

                                <div class="relative p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex min-w-0 flex-1 items-center space-x-4">
                                            <div class="relative flex-shrink-0">
                                                <div class="flex h-12 w-10 items-center justify-center rounded"
                                                    :class="getFileIcon(file).bg">
                                                    <span class="text-xs font-bold" :class="getFileIcon(file).text"
                                                        x-text="getFileIcon(file).label"></span>
                                                </div>

                                                {{-- Status indicator --}}
                                                <div class="absolute -bottom-1 -right-1">
                                                    <template
                                                        x-if="file.status === 'uploading' || file.status === 'pending'">
                                                        <div
                                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-500">
                                                            <svg class="h-3 w-3 animate-spin text-white"
                                                                fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </template>
                                                    <template x-if="file.status === 'completed'">
                                                        <div
                                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500">
                                                            <svg class="h-3 w-3 text-white" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        </div>
                                                    </template>
                                                    <template x-if="file.status === 'error'">
                                                        <div
                                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500">
                                                            <svg class="h-3 w-3 text-white" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100"
                                                    x-text="file.name"></p>
                                                <div class="mt-1 flex items-center space-x-3">
                                                    <span class="text-xs text-zinc-500 dark:text-zinc-400"
                                                        x-text="formatFileSize(file.size)"></span>
                                                    <span class="text-xs text-sky-600 dark:text-sky-400"
                                                        x-show="file.status === 'uploading' || file.status === 'pending'"
                                                        x-cloak x-text="`${file.progress}%`">
                                                    </span>
                                                    <span class="text-xs text-emerald-600 dark:text-emerald-400"
                                                        x-show="file.status === 'completed'" x-cloak>
                                                        Completed
                                                    </span>
                                                    <span class="text-xs text-red-600 dark:text-red-400"
                                                        x-show="file.status === 'error'" x-cloak>
                                                        Upload Failed
                                                    </span>
                                                </div>

                                                {{-- Progress bar --}}
                                                <div x-show="file.status === 'uploading' || file.status === 'pending'"
                                                    x-cloak
                                                    class="mt-2 h-1.5 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                    <div class="h-1.5 rounded-full bg-sky-600 transition-all duration-300 dark:bg-sky-500"
                                                        :style="`width: ${file.progress}%`"></div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Action buttons --}}
                                        <div class="ml-4 flex items-center space-x-2">
                                            <template x-if="file.status === 'uploading' || file.status === 'pending'">
                                                <button @click="cancelUpload(file.id)"
                                                    class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                                    title="Cancel upload">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </template>
                                            <template x-if="file.status === 'error'">
                                                <button @click="retryUpload(file.id)"
                                                    class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-900/20"
                                                    title="Retry upload">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inline upload (when not in modal mode) --}}
    @if (!$modalMode)
        {{-- Messages --}}
        <div x-show="error" x-cloak x-transition
            class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center">
                <svg class="mr-2 h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-red-800 dark:text-red-200" x-text="error"></p>
            </div>
        </div>

        <div x-cloak x-show="success" x-transition
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
            <div class="flex items-center">
                <svg class="mr-2 h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-emerald-800 dark:text-emerald-200" x-text="success"></p>
            </div>
        </div>

        {{-- Upload Area --}}
        <div class="mb-6">
            <label class="group block w-full cursor-pointer">
                <div :class="isDragOver ? 'border-sky-500 bg-sky-50 dark:bg-sky-900/20' : 'border-zinc-300 dark:border-zinc-600'"
                    class="flex h-64 w-full flex-col items-center justify-center rounded-xl border-2 border-dashed transition-all duration-200 hover:border-sky-400 hover:bg-sky-50/50 group-hover:border-sky-400 dark:hover:bg-sky-900/10"
                    @drop.prevent="handleDrop($event)" @dragover.prevent="handleDragOver($event)"
                    @dragenter.prevent="handleDragOver($event)" @dragleave.prevent="handleDragLeave($event)">
                    <div class="space-y-4 text-center">
                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>

                        <div class="space-y-2 text-center">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Choose a file or drag & drop it here.
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->acceptedTypesDisplay }}</p>
                        </div>

                        <span
                            class="inline-block cursor-pointer rounded-lg bg-sky-600 px-6 py-3 text-sm font-medium text-white transition-all hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:bg-sky-700 dark:hover:bg-sky-600">
                            Browse Files
                        </span>
                    </div>
                </div>

                <input type="file" class="sr-only" x-ref="fileInput"
                    @if ($multiple) multiple @endif accept="{{ $this->acceptedTypes }}"
                    @change="handleFileSelect($event)" />
            </label>
        </div>

        {{-- URL Import (if enabled) --}}
        @if ($allowUrlImport)
            <div class="mb-6">
                <button @click="toggleUrlImport()"
                    class="flex items-center text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                    Or import from URL
                </button>

                <div x-show="showUrlImport" x-collapse class="mt-3">
                    <div class="flex gap-2">
                        <input type="url" x-model="importUrl" x-ref="urlInput" placeholder="Enter file URL..."
                            class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" />
                        <button @click="importFromUrl()" :disabled="!importUrl.trim() || importingFromUrl"
                            class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!importingFromUrl">Import</span>
                            <span x-show="importingFromUrl">Importing...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Uploading Files List --}}
        <div x-show="uploadingFiles.length > 0" x-cloak class="mb-6 space-y-3">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Uploading Files</h3>
            <template x-for="file in uploadingFiles" :key="file.id">
                <div
                    class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-transparent transition-all duration-300"
                        :style="`width: ${file.progress}%`"></div>

                    <div class="relative p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex min-w-0 flex-1 items-center space-x-4">
                                <div class="relative flex-shrink-0">
                                    <div class="flex h-12 w-10 items-center justify-center rounded"
                                        :class="getFileIcon(file).bg">
                                        <span class="text-xs font-bold" :class="getFileIcon(file).text"
                                            x-text="getFileIcon(file).label"></span>
                                    </div>

                                    {{-- Status indicator --}}
                                    <div class="absolute -bottom-1 -right-1">
                                        <template x-if="file.status === 'uploading' || file.status === 'pending'">
                                            <div
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-500">
                                                <svg class="h-3 w-3 animate-spin text-white" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                    </path>
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="file.status === 'completed'">
                                            <div
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500">
                                                <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="file.status === 'error'">
                                            <div
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500">
                                                <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100"
                                        x-text="file.name"></p>
                                    <div class="mt-1 flex items-center space-x-3">
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400"
                                            x-text="formatFileSize(file.size)"></span>
                                        <span class="text-xs text-sky-600 dark:text-sky-400"
                                            x-show="file.status === 'uploading' || file.status === 'pending'" x-cloak
                                            x-text="`${file.progress}%`">
                                        </span>
                                        <span class="text-xs text-emerald-600 dark:text-emerald-400"
                                            x-show="file.status === 'completed'" x-cloak>
                                            Completed
                                        </span>
                                        <span class="text-xs text-red-600 dark:text-red-400"
                                            x-show="file.status === 'error'" x-cloak>
                                            Upload Failed
                                        </span>
                                    </div>

                                    {{-- Progress bar --}}
                                    <div x-show="file.status === 'uploading' || file.status === 'pending'" x-cloak
                                        class="mt-2 h-1.5 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div class="h-1.5 rounded-full bg-sky-600 transition-all duration-300 dark:bg-sky-500"
                                            :style="`width: ${file.progress}%`"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Action buttons --}}
                            <div class="ml-4 flex items-center space-x-2">
                                <template x-if="file.status === 'uploading' || file.status === 'pending'">
                                    <button @click="cancelUpload(file.id)"
                                        class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                        title="Cancel upload">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </template>
                                <template x-if="file.status === 'error'">
                                    <button @click="retryUpload(file.id)"
                                        class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-900/20"
                                        title="Retry upload">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    @endif

    {{-- Uploaded Files List (shown in both modal and inline modes) --}}
    <div x-show="completedFiles.length > 0" x-cloak class="mt-4 space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Uploaded Files</h3>
        <template x-for="file in completedFiles" :key="file.key">
            <div
                class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
                <div class="flex items-center space-x-3">
                    <div x-data="{ showSuccess: true }" 
                        x-init="setTimeout(() => showSuccess = false, 2000)">
                        <!-- Success checkmark (shows for first 2 seconds) -->
                        <div x-show="showSuccess" x-cloak class="flex h-10 w-8 items-center justify-center rounded-sm bg-gradient-to-b from-white via-emerald-50 to-emerald-100 border border-emerald-300 shadow-md dark:from-emerald-600 dark:via-emerald-700 dark:to-emerald-800 dark:border-emerald-500">
                            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <!-- File type icon (shows after 2 seconds) -->
                        <div x-show="!showSuccess" x-cloak>
                            <template x-if="file.name.toLowerCase().endsWith('.jpg') || file.name.toLowerCase().endsWith('.jpeg')">
                                @include('support.ui.files.icons.jpg')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.png')">
                                @include('support.ui.files.icons.png')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.mp4')">
                                @include('support.ui.files.icons.mp4')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.mov')">
                                @include('support.ui.files.icons.mov')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.pdf')">
                                @include('support.ui.files.icons.pdf')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.ai')">
                                @include('support.ui.files.icons.ai')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.psd')">
                                @include('support.ui.files.icons.psd')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.xlsx') || file.name.toLowerCase().endsWith('.xls')">
                                @include('support.ui.files.icons.xlsx')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.docx') || file.name.toLowerCase().endsWith('.doc')">
                                @include('support.ui.files.icons.docx')
                            </template>
                            <template x-if="file.name.toLowerCase().endsWith('.pptx') || file.name.toLowerCase().endsWith('.ppt')">
                                @include('support.ui.files.icons.pptx')
                            </template>
                            <!-- Default icon for unknown file types -->
                            <template x-if="!file.name.toLowerCase().match(/\.(jpg|jpeg|png|mp4|mov|pdf|ai|psd|xlsx|xls|docx|doc|pptx|ppt)$/)">
                                @include('support.ui.files.icons.default')
                            </template>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100" x-text="file.name"></p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            <span x-text="formatFileSize(file.size)"></span>
                            <span x-show="file.imported" x-cloak>• Imported from URL</span>
                            <span x-show="!file.imported" x-cloak>• Uploaded</span>
                        </p>
                    </div>
                </div>
                <button @click="removeFile(file.key)"
                    class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                    title="Remove file">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                </button>
            </div>
        </template>
    </div>

</div>

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush
