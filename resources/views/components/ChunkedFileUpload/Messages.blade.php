{{-- Success Messages --}}
<div x-show="success" x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-200"  
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20"
    role="alert"
    aria-live="polite">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200" x-text="success"></p>
        </div>
        <button @click="success = null" class="flex-shrink-0 text-emerald-400 hover:text-emerald-600 dark:text-emerald-500 dark:hover:text-emerald-300">
            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
            <span class="sr-only">Dismiss</span>
        </button>
    </div>
</div>

{{-- Error Messages --}}
<div x-show="error" x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20"
    role="alert"
    aria-live="assertive">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5 text-red-600 dark:text-red-400" />
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-red-800 dark:text-red-200" x-text="error"></p>
            
            {{-- Additional error details if available --}}
            <div x-show="errorDetails" x-cloak class="mt-2">
                <details class="text-xs text-red-700 dark:text-red-300">
                    <summary class="cursor-pointer font-medium hover:text-red-800 dark:hover:text-red-200">
                        Show details
                    </summary>
                    <pre class="mt-1 whitespace-pre-wrap font-mono" x-text="errorDetails"></pre>
                </details>
            </div>
            
            {{-- Retry suggestion for network errors --}}
            <div x-show="error && error.includes('network') || error.includes('timeout')" x-cloak 
                 class="mt-2 text-xs text-red-600 dark:text-red-400">
                <p>This appears to be a network issue. Please check your connection and try again.</p>
            </div>
            
            {{-- File size suggestion --}}
            <div x-show="error && (error.includes('too large') || error.includes('size'))" x-cloak 
                 class="mt-2 text-xs text-red-600 dark:text-red-400">
                <p>Try reducing the file size or contact support if you need to upload larger files.</p>
            </div>
        </div>
        <button @click="clearMessages()" class="flex-shrink-0 text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-300">
            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
            <span class="sr-only">Dismiss</span>
        </button>
    </div>
</div>

{{-- Warning Messages --}}
<div x-show="warning" x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20"
    role="alert"
    aria-live="polite">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200" x-text="warning"></p>
        </div>
        <button @click="warning = null" class="flex-shrink-0 text-amber-400 hover:text-amber-600 dark:text-amber-500 dark:hover:text-amber-300">
            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
            <span class="sr-only">Dismiss</span>
        </button>
    </div>
</div>

{{-- Info Messages --}}
<div x-show="info" x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20"
    role="status"
    aria-live="polite">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-sky-800 dark:text-sky-200" x-text="info"></p>
        </div>
        <button @click="info = null" class="flex-shrink-0 text-sky-400 hover:text-sky-600 dark:text-sky-500 dark:hover:text-sky-300">
            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
            <span class="sr-only">Dismiss</span>
        </button>
    </div>
</div>

<script>
// Extend the Alpine component to include warning and info message support
document.addEventListener('alpine:init', () => {
    Alpine.data('chunkedFileUploadComponent', (config) => ({
        ...chunkedFileUploadComponent(config),
        
        // Additional message states
        warning: null,
        info: null,
        errorDetails: null,
        
        // Enhanced message methods
        showWarning(message) {
            this.warning = message;
            this.error = null;
            this.success = null;
            this.info = null;
            
            // Auto-clear after 8 seconds
            setTimeout(() => {
                if (this.warning === message) {
                    this.warning = null;
                }
            }, 8000);
        },
        
        showInfo(message) {
            this.info = message;
            this.error = null;
            this.success = null;
            this.warning = null;
            
            // Auto-clear after 6 seconds
            setTimeout(() => {
                if (this.info === message) {
                    this.info = null;
                }
            }, 6000);
        },
        
        showDetailedError(message, details = null) {
            this.error = message;
            this.errorDetails = details;
            this.success = null;
            this.warning = null;
            this.info = null;
            
            // Auto-clear after 15 seconds (longer for errors with details)
            setTimeout(() => {
                if (this.error === message) {
                    this.clearMessages();
                }
            }, 15000);
        },
        
        // Enhanced clear messages
        clearMessages() {
            this.error = null;
            this.success = null;
            this.warning = null;
            this.info = null;
            this.errorDetails = null;
        }
    }));
});
</script>