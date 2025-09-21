<div x-data="{
    isPolling: @js($isPolling),
    pollInterval: null,
    
    init() {
        // Start polling when component initializes
        this.startPolling();
    },
    
    startPolling() {
        if (this.pollInterval) {
            console.log('Polling already active');
            return;
        }
        
        this.isPolling = true;
        
        // Poll every 60 seconds
        this.pollInterval = setInterval(() => {
            if (this.isPolling) {
                @this.call('checkForNewVersions');
            }
        }, 60000); // 60 seconds
        
        console.log('Document version polling started (60s interval)');
    },
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
            console.log('Document version polling stopped');
        }
        
        this.isPolling = false;
    },
    
    destroy() {
        this.stopPolling();
    }
}" 
x-init="init()"
@destroy="destroy()"
class="flex h-full flex-col">
    
    {{-- Header --}}
    <div class="flex-shrink-0 px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                Version History
            </h3>
            <div class="flex items-center gap-2">
                <!-- Close Button -->
                <button @click="$dispatch('close')" 
                        class="rounded-md p-1.5 text-zinc-400 hover:text-zinc-500 dark:text-zinc-500 dark:hover:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                {{-- Polling Status Indicator --}}
                <div x-show="isPolling" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="flex items-center gap-1 text-xs text-sky-600 dark:text-sky-400">
                    <div class="w-2 h-2 bg-sky-500 rounded-full animate-pulse"></div>
                    <span>Live</span>
                </div>
                
                {{-- Version Count --}}
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ count($this->dummyVersions) }} version{{ count($this->dummyVersions) !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Timeline Container --}}
    <div class="flex-1 overflow-y-auto px-4 py-2">
        {{-- Empty State --}}
        @if(count($this->dummyVersions) === 0)
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-12 h-12 mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                    <svg class="w-6 h-6 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">No versions yet</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500">Changes will appear here automatically</p>
            </div>
        @endif

        {{-- Versions List --}}
        <div class="space-y-2">
            @foreach($this->dummyVersions as $version)
                <livewire:document-version-item
                    :version-id="$version['id']"
                    :created-at="$version['created_at']"
                    :is-current-version="$version['is_current_version']"
                    :key="$version['id']" />
            @endforeach
        </div>

    </div>

</div>

