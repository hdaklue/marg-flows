@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Facades\FilamentView;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $alignment = $getAlignment() ?? Alignment::Start;
    $modalMode = $config['modalMode'] ?? false;
    $showUrlImport = $config['showUrlImport'] ?? false;
    $isPreviewable = $isPreviewable();
    
    if (!$alignment instanceof Alignment) {
        $alignment = filled($alignment) ? Alignment::tryFrom($alignment) ?? $alignment : null;
    }
@endphp

@props([
    'modalMode' => false,
    'showUrlImport' => false,
    'modalTitle' => 'Upload Files',
    'uploadButtonText' => 'Choose Files',
    'urlImportPlaceholder' => 'Enter file URL to import...',
    'dragDropText' => 'Drop files here to upload',
    'clickToUploadText' => 'Click to upload or drag and drop'
])

<div 
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chunkedFileUploadComponent') }}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('chunkedFileUploadCss'))]"
    x-data="chunkedFileUploadComponent({
        acceptedFileTypes: @js($getAcceptedFileTypes()),
        chunkSize: @js($getChunkSize()),
        chunkUploadUrl: @js($getChunkUploadUrl()),
        chunkDeleteUrl: @js($getChunkDeleteUrl()),
        chunkCancelUrl: @js($getChunkCancelUrl()),
        isChunked: @js($isChunked()),
        isDisabled: @js($isDisabled),
        isMultiple: @js($isMultiple()),
        isPreviewable: @js($isPreviewable),
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
        storage: @js($getStorageConfig()),
        modalMode: @js($modalMode),
        showUrlImport: @js($showUrlImport),
        autoFocus: @js($getAutoFocus() ?? true),
    })"
    wire:ignore
    @keydown="handleKeyboardNavigation($event)"
    @touchstart="handleTouchStart($event)"
    @touchmove="handleTouchMove($event)"
    @touchend="handleTouchEnd($event)"
    class="fi-fo-chunked-file-upload-enhanced"
    {{ $attributes->merge([
            'id' => $getId(),
        ], escape: false)
        ->merge($getExtraAttributes(), escape: false)
        ->merge($getExtraAlpineAttributes(), escape: false) }}>

    {{-- Inline Mode --}}
    <div x-show="!config.modalMode" class="space-y-4">
        <x-chunked-file-upload.upload-area :isDisabled="$isDisabled" />
        <x-chunked-file-upload.file-list />
        <x-chunked-file-upload.url-import x-show="config.showUrlImport" />
        <x-chunked-file-upload.messages />
    </div>

    {{-- Modal Trigger Button --}}
    <div x-show="config.modalMode" class="flex items-center justify-center">
        <x-filament::button
            @click="openModal()"
            :disabled="$isDisabled"
            size="lg"
            icon="heroicon-o-cloud-arrow-up"
            class="relative overflow-hidden">
            {{ $uploadButtonText }}
            {{-- File count indicator --}}
            <span 
                x-show="completedFiles.length > 0" 
                x-cloak
                class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-sky-100 text-xs font-medium text-sky-800 dark:bg-sky-900/50 dark:text-sky-200"
                x-text="completedFiles.length">
            </span>
        </x-filament::button>
    </div>

    {{-- Enhanced Modal --}}
    <div 
        x-show="showModal && config.modalMode" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        aria-labelledby="modal-title"
        aria-modal="true"
        role="dialog">
        
        {{-- Modal Backdrop --}}
        <div 
            class="fixed inset-0 bg-zinc-950/75 dark:bg-black/75 backdrop-blur-sm"
            @click="closeModal()"
            aria-hidden="true">
        </div>

        {{-- Modal Content --}}
        <div 
            x-ref="modal"
            tabindex="-1"
            class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-zinc-800 rounded-xl shadow-2xl border border-zinc-200 dark:border-zinc-700 flex flex-col"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop>

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/50">
                        <x-filament::icon 
                            icon="heroicon-o-cloud-arrow-up" 
                            class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                    </div>
                    <div>
                        <h2 id="modal-title" class="text-lg font-semibold text-zinc-950 dark:text-white">
                            {{ $modalTitle }}
                        </h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" x-show="config.maxFiles">
                            Maximum <span x-text="config.maxFiles"></span> files allowed
                        </p>
                    </div>
                </div>
                
                {{-- Close Button --}}
                <x-filament::icon-button
                    icon="heroicon-m-x-mark"
                    @click="closeModal()"
                    size="lg"
                    color="zinc"
                    class="hover:bg-zinc-100 dark:hover:bg-zinc-700"
                    :tooltip="__('filament::actions/modal.actions.close.label')"
                />
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                {{-- Tab Navigation --}}
                <div x-show="config.showUrlImport" class="flex space-x-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded-lg">
                    <button
                        @click="activeTab = 'upload'"
                        :class="activeTab === 'upload' ? 'bg-white dark:bg-zinc-800 shadow-sm' : ''"
                        class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        x-data="{ activeTab: 'upload' }">
                        <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="inline h-4 w-4 mr-2" />
                        Upload Files
                    </button>
                    <button
                        @click="activeTab = 'url'"
                        :class="activeTab === 'url' ? 'bg-white dark:bg-zinc-800 shadow-sm' : ''"
                        class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        <x-filament::icon icon="heroicon-o-link" class="inline h-4 w-4 mr-2" />
                        Import from URL
                    </button>
                </div>

                {{-- Upload Tab --}}
                <div x-show="!config.showUrlImport || activeTab === 'upload'">
                    <x-chunked-file-upload.upload-area 
                        :modalMode="true" 
                        :isDisabled="$isDisabled" 
                        :dragDropText="$dragDropText"
                        :clickToUploadText="$clickToUploadText" />
                </div>

                {{-- URL Import Tab --}}
                <div x-show="config.showUrlImport && activeTab === 'url'" x-cloak>
                    <x-chunked-file-upload.url-import 
                        :placeholder="$urlImportPlaceholder"
                        :modal="true" />
                </div>

                {{-- Progress Section --}}
                <x-chunked-file-upload.progress-section />

                {{-- File List --}}
                <x-chunked-file-upload.file-list :modal="true" />

                {{-- Messages --}}
                <x-chunked-file-upload.messages />
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center space-x-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
                    <span x-show="config.maxSize">
                        Max size: <span x-text="config.maxSize"></span>
                    </span>
                    <span x-show="config.maxSize && config.acceptedFileTypes.length"> • </span>
                    <span x-show="config.acceptedFileTypes.length">
                        Accepted: <span x-text="config.acceptedFileTypes.slice(0, 3).join(', ')"></span>
                        <span x-show="config.acceptedFileTypes.length > 3" x-text="'and ' + (config.acceptedFileTypes.length - 3) + ' more'"></span>
                    </span>
                </div>
                
                <div class="flex items-center space-x-3">
                    {{-- Cancel All Button --}}
                    <x-filament::button
                        x-show="uploading"
                        x-cloak
                        @click="cancelAllUploads()"
                        color="danger"
                        size="sm">
                        Cancel All
                    </x-filament::button>
                    
                    {{-- Close Button --}}
                    <x-filament::button
                        @click="closeModal()"
                        color="zinc"
                        size="sm">
                        Close
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Reader Announcements --}}
    <div aria-live="polite" aria-atomic="true" class="sr-only"></div>
    <div aria-live="assertive" aria-atomic="true" class="sr-only"></div>
</div>