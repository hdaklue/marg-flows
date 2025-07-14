@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Facades\FilamentView;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $alignment = $getAlignment() ?? Alignment::Start;

    if (!$alignment instanceof Alignment) {
        $alignment = filled($alignment) ? Alignment::tryFrom($alignment) ?? $alignment : null;
    }
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :label-sr-only="$isLabelHidden()">
    <div x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chunkedFileUploadComponent') }}"
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('chunkedFileUploadCss'))]" x-data="chunkedFileUploadComponent({
            acceptedFileTypes: @js($getAcceptedFileTypes()),
            chunkSize: @js($getChunkSize()),
            chunkUploadUrl: @js($getChunkUploadUrl()),
            chunkDeleteUrl: @js($getChunkDeleteUrl()),
            chunkCancelUrl: @js($getChunkCancelUrl()),
            isChunked: @js($isChunked()),
            isDisabled: @js($isDisabled),
            isMultiple: @js($isMultiple()),
            isPreviewable: @js($isPreviewable()),
            isImageUpload: @js($isImageUpload()),
            isVideoUpload: @js($isVideoUpload()),
            maxFiles: @js($getMaxFiles()),
            maxParallelUploads: @js($getMaxParallelUploads()),
            maxSize: @js(($size = $getMaxSize()) ? "{$size}KB" : null),
            minSize: @js(($size = $getMinSize()) ? "{$size}KB" : null),
            placeholder: @js($getPlaceholder()),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            uploadingMessage: @js($getUploadingMessage()),
        })" wire:ignore
        {{ $attributes->merge(
                [
                    'id' => $getId(),
                ],
                escape: false,
            )->merge($getExtraAttributes(), escape: false)->merge($getExtraAlpineAttributes(), escape: false)->class([
                'fi-fo-chunked-file-upload flex flex-col gap-y-4',
                match ($alignment) {
                    Alignment::Start, Alignment::Left => 'items-start',
                    Alignment::Center => 'items-center',
                    Alignment::End, Alignment::Right => 'items-end',
                    default => $alignment,
                },
            ]) }}>
        <!-- File Input -->
        <div class="w-full">
            <label
                class="fi-fo-file-upload-dropzone flex h-32 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 transition-colors hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:hover:bg-gray-700 drag-over:border-blue-500 drag-over:bg-blue-50 dark:drag-over:border-blue-400 dark:drag-over:bg-blue-900/20">
                <div class="flex flex-col items-center justify-center p-6">
                    <x-filament::icon icon="heroicon-o-cloud-arrow-up"
                        class="mb-4 h-8 w-8 text-gray-500 dark:text-gray-400" />
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-semibold">Click to upload</span> or drag and drop
                    </p>
                    {{-- <p class="text-xs text-gray-500 dark:text-gray-400" x-show="config.placeholder" x-cloak
                        x-text="config.placeholder"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-show="config.maxSize" x-cloak>Max size: <span
                            x-text="config.maxSize"></span></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-show="config.isChunked" x-cloak>Chunked upload
                        enabled
                        ({{ $getChunkSizeFormatted() }} chunks)</p> --}}
                </div>
                <input x-ref="fileInput" type="file" class="hidden" :multiple="config.isMultiple"
                    :accept="config.acceptedFileTypes.join(',')" :disabled="config.isDisabled"
                    @change="handleFileSelect($event)" />
            </label>
        </div>

        <!-- Upload Progress -->
        <div x-show="uploading" x-cloak class="fi-fo-file-upload-progress w-full space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-medium text-gray-950 dark:text-white">
                    Uploading Files...
                </h4>
                <x-filament::button color="danger" size="xs" @click="cancelAllUploads()">
                    Cancel All
                </x-filament::button>
            </div>

            <template x-for="file in uploadingFiles" :key="file.id">
                <div class="fi-fo-file-upload-item group relative w-full overflow-hidden rounded-lg border transition-all duration-200"
                    x-bind:class="file.status === 'error' ? 'border-red-200 bg-red-50 dark:border-red-700 dark:bg-red-900/10' :
                        (file.status === 'uploading' ?
                            'border-blue-200 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/10' :
                            'border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800')"
                    <!-- Progress Bar Background -->
                    <div class="absolute inset-0 transition-all duration-300"
                        x-bind:class="file.status === 'uploading' ? 'bg-blue-100/30 dark:bg-blue-800/20' : ''"
                        x-bind:style="file.status === 'uploading' ? `width: ${file.progress}%` : 'width: 0%'">
                    </div>

                    <div class="relative p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex min-w-0 flex-1 items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <!-- Status Icon -->
                                    <div class="relative flex h-8 w-8 items-center justify-center">
                                        <!-- Base Document Icon - show when pending -->
                                        <x-filament::icon icon="heroicon-o-document-text"
                                            class="h-6 w-6 transition-colors duration-200"
                                            x-show="file.status === 'pending'" x-cloak
                                            x-bind:class="'text-gray-400 dark:text-gray-500'" />

                                        <!-- Loading Spinner for Uploading -->
                                        <x-filament::icon icon="heroicon-o-arrow-path"
                                            class="h-6 w-6 animate-spin text-blue-500 dark:text-blue-400"
                                            x-show="file.status === 'uploading'" x-cloak />

                                        <!-- Success Icon for Completed -->
                                        <x-filament::icon icon="heroicon-o-check-circle"
                                            class="h-6 w-6 text-green-500 dark:text-green-400"
                                            x-show="file.status === 'completed'" x-cloak />

                                        <!-- Error Icon for Failed -->
                                        <x-filament::icon icon="heroicon-o-x-circle"
                                            class="h-6 w-6 text-red-500 dark:text-red-400"
                                            x-show="file.status === 'error'" x-cloak />
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex w-full items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium transition-colors duration-200"
                                                x-bind:class="file.status === 'error' ? 'text-red-800 dark:text-red-200' :
                                                    (file.status === 'uploading' ? 'text-blue-800 dark:text-blue-200' :
                                                        (file.status === 'completed' ?
                                                            'text-green-800 dark:text-green-200' :
                                                            'text-gray-950 dark:text-gray-100'))"
                                                x-text="file.name"></p>
                                            <div class="mt-1 flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs transition-colors duration-200"
                                                        x-bind:class="file.status === 'error' ? 'text-red-600 dark:text-red-400' :
                                                            (file.status === 'uploading' ?
                                                                'text-blue-600 dark:text-blue-400' :
                                                                (file.status === 'completed' ?
                                                                    'text-green-600 dark:text-green-400' :
                                                                    'text-gray-500 dark:text-gray-400'))"
                                                        x-text="formatFileSize(file.size)"></span>

                                                    <span
                                                        class="text-xs font-medium capitalize transition-colors duration-200"
                                                        x-bind:class="file.status === 'error' ? 'text-red-600 dark:text-red-400' :
                                                            (file.status === 'uploading' ?
                                                                'text-blue-600 dark:text-blue-400' :
                                                                (file.status === 'completed' ?
                                                                    'text-green-600 dark:text-green-400' :
                                                                    'text-gray-500 dark:text-gray-400'))"
                                                        x-text="file.status === 'uploading' ? `${file.progress}%` : file.status"></span>
                                                </div>

                                                <!-- Upload Speed - Better positioned -->
                                                <span class="font-mono text-xs text-blue-600 dark:text-blue-400"
                                                    x-show="file.speed && file.status === 'uploading'" x-cloak>
                                                    <span x-text="file.speed"></span>/s
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="ml-3 flex items-center space-x-2">
                                            <!-- Cancel Button -->
                                            <x-filament::button color="danger" size="xs"
                                                x-show="file.status === 'uploading'" x-cloak
                                                @click="cancelUpload(file.id)">
                                                <x-filament::icon icon="heroicon-m-x-mark" class="h-3 w-3" />
                                            </x-filament::button>

                                            <!-- Retry Button for Failed Uploads -->
                                            <x-filament::button color="primary" size="xs"
                                                x-show="file.status === 'error'" x-cloak @click="retryUpload(file.id)">
                                                <x-filament::icon icon="heroicon-m-arrow-path" class="h-3 w-3" />
                                                Retry
                                            </x-filament::button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div x-show="file.status === 'uploading'" x-cloak class="mt-3">
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600">
                                <div class="h-full rounded-full bg-blue-500 transition-all duration-300 dark:bg-blue-400"
                                    x-bind:style="`width: ${file.progress}%`"></div>
                            </div>
                        </div>

                        <!-- Chunk Progress -->
                        <div x-show="file.chunks && file.chunks.length > 1 && file.status === 'uploading'" x-cloak
                            class="mt-2">
                            <div class="flex items-center space-x-1 text-xs text-blue-600 dark:text-blue-400">
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="h-3 w-3" />
                                <span>Chunk <span x-text="file.uploadedChunks"></span> of <span
                                        x-text="file.totalChunks"></span></span>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div x-show="file.status === 'error' && file.error" x-cloak class="mt-2">
                            <div class="flex items-center space-x-1 text-xs text-red-600 dark:text-red-400">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-3 w-3" />
                                <span x-text="file.error"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Uploaded Files -->
        <div x-show="uploadedFiles.length > 0" x-cloak class="fi-fo-file-upload-files w-full space-y-2">
            <h4 class="text-sm font-medium text-gray-950 dark:text-white">Uploaded Files</h4>
            <template x-for="file in uploadedFiles" :key="file.key">
                <div
                    class="fi-fo-file-upload-file group relative w-full overflow-hidden rounded-lg border border-green-200 bg-green-50 transition-all duration-200 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:hover:bg-green-900/30">
                    <!-- Success Background -->
                    <div class="absolute inset-0 bg-green-100/30 dark:bg-green-800/10" style="width: 100%"></div>

                    <div class="relative flex w-full items-center justify-between p-4">
                        <div class="flex min-w-0 flex-1 items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="flex h-8 w-8 items-center justify-center">
                                    <x-filament::icon icon="heroicon-o-check-circle"
                                        class="h-6 w-6 text-green-500 dark:text-green-400" />
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-green-800 dark:text-green-200"
                                    x-text="file.name"></p>
                                <div class="mt-1 flex items-center space-x-2">
                                    <span class="text-xs text-green-600 dark:text-green-400"
                                        x-text="formatFileSize(file.size)"></span>
                                    <span
                                        class="text-xs font-medium text-green-600 dark:text-green-400">completed</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <!-- View/Download Button - only show if previewable is enabled -->
                            <x-filament::icon-button icon="heroicon-o-eye" color="success" size="sm"
                                x-show="config.isPreviewable && file.url" x-cloak @click="window.open(file.url, '_blank')" />

                            <!-- Remove Button -->
                            <x-filament::icon-button icon="heroicon-o-trash" color="danger" size="sm"
                                @click="removeFile(file.key)" />
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Error Messages -->
        <div x-show="error" x-cloak
            class="fi-banner fi-color-danger border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-400 rounded-lg border px-3 py-2 text-sm">
            <span x-text="error"></span>
        </div>
    </div>
</x-dynamic-component>
