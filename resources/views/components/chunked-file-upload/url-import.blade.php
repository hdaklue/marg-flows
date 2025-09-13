@props([
    'placeholder' => 'Enter file URL to import...',
    'modal' => false
])

<div class="space-y-4">
    {{-- URL Import Header --}}
    <div class="flex items-center space-x-2 text-sm text-zinc-700 dark:text-zinc-300">
        <x-filament::icon icon="heroicon-o-link" class="h-4 w-4" />
        <span class="font-medium">Import from URL</span>
    </div>

    {{-- URL Input Form --}}
    <div class="space-y-3">
        <div class="relative">
            <x-filament::input
                x-ref="urlInput"
                x-model="importUrl"
                type="url"
                :placeholder="$placeholder"
                class="pr-12"
                :disabled="$attributes->get('disabled', false) || 'importingFromUrl'"
                @keydown.enter.prevent="importFromUrl()"
                @input="if($event.target.value.trim() === '') error = null" />
            
            {{-- URL Validation Icon --}}
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <x-filament::icon 
                    x-show="importUrl && !importingFromUrl && isValidUrl(importUrl)"
                    x-cloak
                    icon="heroicon-o-check-circle" 
                    class="h-4 w-4 text-emerald-500" />
                    
                <x-filament::icon 
                    x-show="importUrl && !importingFromUrl && !isValidUrl(importUrl)"
                    x-cloak
                    icon="heroicon-o-x-circle" 
                    class="h-4 w-4 text-red-500" />
                    
                <x-filament::loading-indicator
                    x-show="importingFromUrl"
                    x-cloak
                    class="h-4 w-4 text-sky-500" />
            </div>
        </div>

        {{-- Import Button --}}
        <div class="flex items-center justify-between">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                <p>Supported: Images, videos, documents, archives</p>
                <p class="mt-1">Files will be downloaded and uploaded to your storage</p>
            </div>
            
            <x-filament::button
                @click="importFromUrl()"
                :disabled="'!importUrl.trim() || importingFromUrl || !isValidUrl(importUrl)'"
                color="sky"
                size="sm"
                icon="heroicon-o-arrow-down-tray">
                <span x-show="!importingFromUrl">Import</span>
                <span x-show="importingFromUrl" x-cloak>Importing...</span>
            </x-filament::button>
        </div>
    </div>

    {{-- URL Examples/Help --}}
    <div x-show="!importUrl && !importingFromUrl" 
         class="rounded-lg bg-sky-50 p-3 dark:bg-sky-900/20">
        <div class="flex items-start space-x-2">
            <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4 mt-0.5 text-sky-600 dark:text-sky-400 flex-shrink-0" />
            <div class="text-xs text-sky-700 dark:text-sky-300">
                <p class="font-medium mb-1">Examples of supported URLs:</p>
                <ul class="space-y-1 font-mono">
                    <li>• https://example.com/image.jpg</li>
                    <li>• https://example.com/document.pdf</li>
                    <li>• https://example.com/video.mp4</li>
                </ul>
                <p class="mt-2 font-normal">The file will be downloaded and uploaded to your storage with chunked upload support.</p>
            </div>
        </div>
    </div>

    {{-- Import Progress --}}
    <div x-show="importingFromUrl" x-cloak
         class="rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-700 dark:bg-sky-900/20">
        <div class="flex items-center space-x-3">
            <x-filament::loading-indicator class="h-5 w-5 text-sky-500" />
            <div class="flex-1">
                <p class="text-sm font-medium text-sky-800 dark:text-sky-200">
                    Importing from URL...
                </p>
                <p class="text-xs text-sky-600 dark:text-sky-400 mt-1 truncate" x-text="importUrl"></p>
            </div>
        </div>
        
        {{-- Import Steps --}}
        <div class="mt-3 space-y-2">
            <div class="flex items-center space-x-2 text-xs text-sky-600 dark:text-sky-400">
                <div class="h-1.5 w-1.5 rounded-full bg-sky-500"></div>
                <span>Fetching file information...</span>
            </div>
            <div class="flex items-center space-x-2 text-xs text-sky-600 dark:text-sky-400 opacity-50">
                <div class="h-1.5 w-1.5 rounded-full bg-sky-300"></div>
                <span>Downloading file...</span>
            </div>
            <div class="flex items-center space-x-2 text-xs text-sky-600 dark:text-sky-400 opacity-50">
                <div class="h-1.5 w-1.5 rounded-full bg-sky-300"></div>
                <span>Uploading to storage...</span>
            </div>
        </div>
    </div>
</div>

<script>
// Add URL validation helper to the Alpine component
document.addEventListener('alpine:init', () => {
    Alpine.data('chunkedFileUploadComponent', (config) => ({
        ...chunkedFileUploadComponent(config),
        
        isValidUrl(url) {
            if (!url || url.trim() === '') return false;
            
            try {
                const urlObj = new URL(url.trim());
                return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
            } catch {
                return false;
            }
        }
    }));
});
</script>