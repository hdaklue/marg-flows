@props([
    'modal' => false
])

{{-- Uploaded Files --}}
<div x-show="completedFiles.length > 0" x-cloak class="space-y-3">
    <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-zinc-950 dark:text-white flex items-center">
            <x-filament::icon icon="heroicon-o-check-circle" class="mr-2 h-4 w-4 text-emerald-500" />
            Uploaded Files
            <span class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-medium text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200"
                x-text="completedFiles.length"></span>
        </h4>
        
        {{-- Clear All Button --}}
        <x-filament::button 
            x-show="completedFiles.length > 1"
            @click="if(confirm('Remove all uploaded files?')) { completedFiles = []; updateLivewireState(); }"
            size="xs" 
            color="zinc"
            icon="heroicon-o-trash">
            Clear All
        </x-filament::button>
    </div>

    <div class="grid gap-3" :class="{ 'grid-cols-1': !modal, 'grid-cols-1 sm:grid-cols-2': modal }">
        <template x-for="file in completedFiles" :key="file.key">
            <div class="group relative overflow-hidden rounded-lg border border-emerald-200 bg-emerald-50 transition-all duration-200 hover:shadow-md dark:border-emerald-700 dark:bg-emerald-900/20">
                {{-- Success Background Gradient --}}
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-100/50 to-emerald-50/50 dark:from-emerald-800/10 dark:to-emerald-900/10"></div>

                <div class="relative p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex min-w-0 flex-1 items-start space-x-3">
                            {{-- File Icon --}}
                            <div class="flex-shrink-0">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/50">
                                    <x-filament::icon 
                                        :icon="'heroicon-o-document'" 
                                        class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                                        x-bind:icon="getFileIcon(file)" />
                                </div>
                            </div>

                            {{-- File Details --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center space-x-2">
                                    <h5 class="truncate text-sm font-medium text-emerald-800 dark:text-emerald-200"
                                        x-text="file.name"
                                        :title="file.name"></h5>
                                    
                                    {{-- Import Badge --}}
                                    <span x-show="file.imported" x-cloak
                                        class="inline-flex items-center rounded-md bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-400 dark:ring-sky-400/20">
                                        <x-filament::icon icon="heroicon-o-link" class="mr-1 h-3 w-3" />
                                        Imported
                                    </span>
                                </div>
                                
                                <div class="mt-1 flex items-center space-x-3">
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400"
                                        x-text="formatFileSize(file.size)"></span>
                                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        Completed
                                    </span>
                                </div>
                                
                                {{-- File URL (if available) --}}
                                <div x-show="file.url" x-cloak class="mt-2">
                                    <div class="flex items-center space-x-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        <x-filament::icon icon="heroicon-o-link" class="h-3 w-3" />
                                        <span class="truncate font-mono" x-text="file.url" :title="file.url"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center space-x-2">
                            {{-- Preview/View Button --}}
                            <x-filament::icon-button
                                x-show="file.url && (config.isPreviewable || file.type?.startsWith('image/'))"
                                x-cloak
                                icon="heroicon-o-eye"
                                color="emerald"
                                size="sm"
                                @click="window.open(file.url, '_blank')"
                                :tooltip="'View file'" />

                            {{-- Download Button --}}
                            <x-filament::icon-button
                                x-show="file.url"
                                x-cloak
                                icon="heroicon-o-arrow-down-tray"
                                color="zinc"
                                size="sm"
                                @click="(() => { const a = document.createElement('a'); a.href = file.url; a.download = file.name; a.click(); })()"
                                :tooltip="'Download file'" />

                            {{-- Remove Button --}}
                            <x-filament::icon-button
                                icon="heroicon-o-trash"
                                color="danger"
                                size="sm"
                                @click="removeFile(file.key)"
                                :tooltip="'Remove file'" />
                        </div>
                    </div>

                    {{-- File Preview (for images) --}}
                    <div x-show="file.type?.startsWith('image/') && file.url && config.isPreviewable" 
                         x-cloak 
                         class="mt-3">
                        <div class="overflow-hidden rounded-md border border-emerald-200 dark:border-emerald-700">
                            <img 
                                :src="file.url" 
                                :alt="file.name"
                                class="h-24 w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                loading="lazy"
                                @error="$el.parentElement.style.display = 'none'" />
                        </div>
                    </div>
                </div>

                {{-- Loading Overlay for operations --}}
                <div x-show="false" x-cloak 
                     class="absolute inset-0 bg-white/75 dark:bg-zinc-800/75 backdrop-blur-sm flex items-center justify-center">
                    <div class="flex items-center space-x-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-filament::loading-indicator class="h-4 w-4" />
                        <span>Processing...</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>