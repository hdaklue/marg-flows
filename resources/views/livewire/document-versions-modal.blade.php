{{-- Unified Document Versions Modal with Sidebar --}}
<div class="fixed inset-0 z-50 overflow-hidden bg-white dark:bg-zinc-900" wire:key="versions-{{ $documentId }}">
    <div class="flex h-full">
        {{-- Sidebar - Version List (LTR: left, RTL: right) --}}
        <div class="flex flex-col w-80 border-r border-zinc-200 dark:border-zinc-700 ltr:border-r rtl:border-l">
            {{-- Sidebar Header --}}
            <div class="flex items-center justify-between flex-shrink-0 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Document Versions</h2>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    wire:click="$dispatch('closeModal')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Version List with Infinite Scroll --}}
            <div class="flex-1 overflow-y-auto">
                <div class="p-2 space-y-1">
                    @foreach ($loadedVersions as $version)
                        <div wire:key="version-{{ $version->id }}" 
                             class="relative group rounded-lg border p-3 cursor-pointer transition-colors duration-200
                                    {{ $selectedVersionId === $version->id 
                                        ? 'border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-950/20' 
                                        : 'border-transparent hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}"
                             wire:click="selectVersion('{{ $version->id }}')">
                            
                            {{-- Version Info --}}
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    {{-- Version ID (last 6 chars) --}}
                                    <div class="font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ substr($version->id, -6) }}
                                    </div>

                                    {{-- Current Version Badge --}}
                                    @if ($currentEditingVersion === $version->id)
                                        <span class="inline-flex items-center rounded-full bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">
                                            Current
                                        </span>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                @if ($currentEditingVersion !== $version->id)
                                    <button wire:click.stop="applyVersion('{{ $version->id }}')"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 inline-flex items-center justify-center w-6 h-6 text-xs font-medium text-sky-600 bg-transparent border border-transparent rounded hover:bg-sky-100 hover:text-sky-700 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:text-sky-400 dark:hover:bg-sky-900/50 dark:hover:text-sky-300 dark:focus:ring-sky-400"
                                        title="Apply Version">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            {{-- Date --}}
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"
                                 title="{{ $version->created_at->format('M j, Y \a\t g:i A') }}">
                                {{ toUserDiffForHuman($version->created_at->toISOString(), filamentUser()) }}
                            </div>

                            {{-- Creator Name --}}
                            @if ($version->creator?->name)
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $version->creator->name }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Infinite Scroll Trigger --}}
                    @if ($hasMoreVersions)
                        <div x-intersect="$wire.loadMoreVersions()" class="flex items-center justify-center py-4">
                            @if ($isLoading)
                                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Loading more versions...
                                </div>
                            @else
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    Scroll for more versions
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Content - Document Preview --}}
        <div class="flex-1 flex flex-col">
            {{-- Content Header --}}
            @if ($this->selectedVersion)
                <div class="flex items-center justify-between flex-shrink-0 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center space-x-3">
                        <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Preview Mode - Read Only</span>
                        <span class="text-sm font-mono text-zinc-500 dark:text-zinc-400">
                            {{ substr($this->selectedVersion->id, -6) }}
                        </span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->selectedVersion->created_at->format('M j, Y \a\t g:i A') }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Document Content --}}
            <div class="flex-1 overflow-y-auto p-6">
                @if (!$this->selectedVersion || empty($this->selectedVersion->content))
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $this->selectedVersion ? 'No Content Available' : 'Select a Version' }}
                            </h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $this->selectedVersion 
                                    ? 'This version appears to be empty or corrupted.' 
                                    : 'Choose a version from the sidebar to preview its content.' }}
                            </p>
                        </div>
                    </div>
                @else
                    {{-- Document Editor (Read-Only) --}}
                    <div wire:key="editor-{{ $selectedVersionId }}" 
                         x-load
                         x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('documentEditor') }}"
                         x-data="documentEditor(@js($this->selectedVersion->content), '', @js(false), null, 0, '', @js($this->getFullToolsConfig()), @js($this->getAllowedTools()))" 
                         class="w-full max-w-4xl mx-auto">

                        {{-- Document Editor Container --}}
                        <div id="editor-wrap" wire:ignore class="w-full">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Pass translations to JavaScript --}}
    <script>
        window.Laravel = window.Laravel || {};
        window.Laravel.translations = @js($this->getJavaScriptTranslations());
    </script>

    @vite(['resources/css/components/editorjs/index.css', 'resources/css/components/editorjs/comment-tune.css', 'resources/css/components/document/document.css'])
</div>