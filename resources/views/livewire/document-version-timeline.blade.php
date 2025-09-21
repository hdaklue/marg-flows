<div x-data="{
    versions: @js($this->versions->toArray()),
    currentEditingVersion: @js($currentEditingVersion),
    isPolling: @js($isPolling),
    pollInterval: null,
    showAll: false,
    maxVisibleVersions: 5,
    
    get visibleVersions() {
        return this.showAll ? this.versions : this.versions.slice(0, this.maxVisibleVersions);
    },
    
    get hasMoreVersions() {
        return this.versions.length > this.maxVisibleVersions;
    },
    
    init() {
        // Start polling when component initializes
        this.startPolling();
        
        // Listen for new versions from server
        this.$watch('isPolling', (value) => {
            if (value) {
                this.startPolling();
            } else {
                this.stopPolling();
            }
        });
        
        // Listen for new versions event
        window.addEventListener('new-versions-found', (event) => {
            this.handleNewVersions(event.detail.versions);
        });
    },
    
    startPolling() {
        if (this.pollInterval) return;
        
        this.isPolling = true;
        @this.call('startPolling');
        
        // Poll every 60 seconds
        this.pollInterval = setInterval(() => {
            @this.call('checkForNewVersions');
        }, 60000); // 60 seconds
        
        console.log('Document version polling started');
    },
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        
        this.isPolling = false;
        @this.call('stopPolling');
        
        console.log('Document version polling stopped');
    },
    
    handleNewVersions(newVersions) {
        // Add new versions to the top of the list
        this.versions = [...newVersions, ...this.versions];
        
        // Show notification for new versions
        if (newVersions.length > 0) {
            this.$dispatch('notify', {
                type: 'info',
                message: `${newVersions.length} new version${newVersions.length > 1 ? 's' : ''} found`
            });
        }
    },
    
    formatRelativeTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffSecs < 60) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    },
    
    getContentPreview(content) {
        if (!content) return 'Empty version';
        return content;
    },
    
    selectVersion(versionId) {
        this.currentEditingVersion = versionId;
        @this.call('handleVersionSelection', versionId);
    },
    
    destroy() {
        this.stopPolling();
    }
}" 
x-init="init()"
@destroy="destroy()"
class="flex flex-col h-full max-h-screen">
    
    {{-- Header --}}
    <div class="flex-shrink-0 px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                Version History
            </h3>
            <div class="flex items-center gap-2">
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
                <span class="text-xs text-zinc-500 dark:text-zinc-400" 
                      x-text="`${versions.length} version${versions.length !== 1 ? 's' : ''}`">
                </span>
            </div>
        </div>
    </div>

    {{-- Timeline Container --}}
    <div class="flex-1 overflow-y-auto px-4 py-2">
        {{-- Empty State --}}
        <div x-show="versions.length === 0" 
             class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-12 h-12 mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                <svg class="w-6 h-6 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">No versions yet</p>
            <p class="text-xs text-zinc-400 dark:text-zinc-500">Changes will appear here automatically</p>
        </div>

        {{-- Timeline --}}
        <div x-show="versions.length > 0" class="relative">
            {{-- Timeline Line --}}
            <div class="absolute left-4 top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700" 
                 x-show="versions.length > 1"></div>
            
            {{-- Version Items --}}
            <div class="space-y-3">
                <template x-for="(version, index) in visibleVersions" :key="version.id">
                    <div class="relative flex items-start gap-3 group"
                         :class="{
                             'opacity-100': true,
                             'cursor-pointer': version.id !== currentEditingVersion
                         }"
                         @click="version.id !== currentEditingVersion && selectVersion(version.id)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0">
                        
                        {{-- Timeline Dot --}}
                        <div class="relative flex-shrink-0 w-8 h-8 flex items-center justify-center">
                            {{-- Current Version Indicator --}}
                            <div x-show="version.id === currentEditingVersion"
                                 class="w-3 h-3 bg-sky-500 rounded-full ring-4 ring-sky-100 dark:ring-sky-900/50 shadow-sm">
                            </div>
                            
                            {{-- Regular Version Dot --}}
                            <div x-show="version.id !== currentEditingVersion"
                                 class="w-2 h-2 bg-zinc-300 dark:bg-zinc-600 rounded-full group-hover:bg-sky-400 dark:group-hover:bg-sky-500 transition-colors duration-200">
                            </div>
                            
                            {{-- New Version Pulse (for versions less than 10 seconds old) --}}
                            <div x-show="version.id !== currentEditingVersion && (Date.now() - new Date(version.created_at).getTime()) < 10000"
                                 class="absolute inset-0 w-2 h-2 mx-auto my-auto bg-emerald-400 rounded-full animate-ping">
                            </div>
                        </div>

                        {{-- Version Content --}}
                        <div class="flex-1 min-w-0 pb-3"
                             :class="{
                                 'bg-sky-50 dark:bg-sky-950/20 -mx-2 px-2 py-2 rounded-lg border border-sky-200 dark:border-sky-800': version.id === currentEditingVersion,
                                 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50 -mx-2 px-2 py-2 rounded-lg transition-colors duration-200': version.id !== currentEditingVersion
                             }">
                            
                            {{-- Version Header --}}
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    {{-- Current Version Badge --}}
                                    <span x-show="version.id === currentEditingVersion"
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">
                                        Current
                                    </span>
                                    
                                    {{-- Auto-save Badge --}}
                                    <span x-show="version.is_auto_save"
                                          class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                                        Auto
                                    </span>
                                </div>
                                
                                {{-- Timestamp --}}
                                <span class="text-xs text-zinc-500 dark:text-zinc-400"
                                      :title="new Date(version.created_at).toLocaleString()"
                                      x-text="formatRelativeTime(version.created_at)">
                                </span>
                            </div>

                            {{-- Content Preview --}}
                            <div class="mb-2">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed"
                                   x-text="getContentPreview(version.content_preview)">
                                </p>
                            </div>

                            {{-- Version Meta --}}
                            <div class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                                {{-- Author --}}
                                <span x-show="version.author" 
                                      x-text="version.author">
                                </span>
                                
                                {{-- Word Count --}}
                                <span x-show="version.word_count"
                                      x-text="`${version.word_count} words`">
                                </span>
                                
                                {{-- Character Count --}}
                                <span x-show="version.char_count"
                                      x-text="`${version.char_count} chars`">
                                </span>
                                
                                {{-- Block Count --}}
                                <span x-show="version.block_count"
                                      x-text="`${version.block_count} blocks`">
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Show More Button --}}
        <div x-show="hasMoreVersions && !showAll" 
             class="mt-4 text-center">
            <button @click="showAll = true"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-950/20 rounded-md transition-colors duration-200">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                Show all versions
            </button>
        </div>

        {{-- Show Less Button --}}
        <div x-show="showAll && hasMoreVersions" 
             class="mt-4 text-center">
            <button @click="showAll = false"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-950/20 rounded-md transition-colors duration-200">
                <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                Show less
            </button>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex-shrink-0 px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center justify-between">
            {{-- Compare Versions Button --}}
            <button @click="$dispatch('compare-versions')"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition-colors duration-200">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z"></path>
                </svg>
                Compare
            </button>
            
            {{-- Polling Toggle Button --}}
            <button @click="isPolling ? stopPolling() : startPolling()"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200"
                    :class="isPolling ? 'text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-950/20' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="isPolling" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <path x-show="!isPolling" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-text="isPolling ? 'Pause' : 'Resume'"></span>
            </button>
        </div>
    </div>
</div>

