{{-- Unified Document Versions Modal with Sidebar --}}
<div class="fixed inset-0 z-50 overflow-hidden bg-white dark:bg-zinc-900" wire:key="versions-{{ $documentId }}">
    {{-- Mobile Overlay (when sidebar is open on mobile) --}}
    @if (!$sidebarCollapsed)
        <div class="fixed inset-0 z-40 bg-black/50 md:hidden" wire:click="toggleSidebar"></div>
    @endif

    <div class="relative flex h-full">
        {{-- Sidebar - Version List (LTR: left, RTL: right) --}}
        <div class="{{ $sidebarCollapsed ? 'w-0 md:w-80 overflow-hidden md:overflow-visible -translate-x-full md:translate-x-0' : 'w-80' }} absolute z-50 flex h-full flex-col border-r border-zinc-200 bg-white transition-all duration-300 md:relative md:z-auto md:bg-transparent ltr:border-r rtl:border-l dark:border-zinc-700 dark:bg-zinc-900"
            x-data="{
                isMobile: window.innerWidth < 768,
                init() {
                    const updateMobile = () => {
                        this.isMobile = window.innerWidth < 768;
                        if (!this.isMobile && @js($sidebarCollapsed)) {
                            $wire.toggleSidebar();
                        }
                    };
                    window.addEventListener('resize', updateMobile);
                    updateMobile();
                }
            }">
            {{-- Sidebar Header --}}
            <div
                class="flex items-center justify-between flex-shrink-0 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('document.versions.title') }}</h2>
                {{-- Mobile-only sidebar close button (closes sidebar, not modal) --}}
                <button type="button" class="text-zinc-400 hover:text-zinc-600 md:hidden dark:hover:text-zinc-300"
                    wire:click="toggleSidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            {{-- Version List with Infinite Scroll --}}
            <div class="flex-1 overflow-y-auto">
                <div class="p-2 space-y-1">
                    @foreach ($loadedVersions as $version)
                        <div wire:key="version-{{ $version->id }}"
                            class="{{ $selectedVersionId === $version->id
                                ? 'border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-950/20'
                                : 'border-transparent hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} group relative cursor-pointer rounded-lg border p-3 transition-colors duration-200"
                            wire:click="selectVersion('{{ $version->id }}')">

                            {{-- Version Info --}}
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    {{-- Version ID (last 6 chars) --}}
                                    <div class="font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ substr($version->id, -6) }}
                                    </div>

                                    {{-- Current Version Badge --}}
                                    @if ($this->currentVersionId === $version->id)
                                        <span
                                            class="inline-flex items-center rounded-full bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">
                                            {{ __('document.versions.current') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                @if ($this->currentVersionId !== $version->id)
                                    <button wire:click.stop="applyVersion('{{ $version->id }}')"
                                        class="inline-flex items-center justify-center w-6 h-6 text-xs font-medium bg-transparent border border-transparent rounded text-sky-600 hover:bg-sky-100 hover:text-sky-700 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:text-sky-400 dark:hover:bg-sky-900/50 dark:hover:text-sky-300 dark:focus:ring-sky-400"
                                        title="{{ __('document.versions.apply_version') }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m0 0l-4-4m4 4l4-4" />
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            {{-- Date --}}
                            <div class="mb-1 text-xs text-zinc-500 dark:text-zinc-400"
                                title="{{ $version->created_at->format('M j, Y \a\t g:i A') }}">
                                {{ toUserDiffForHuman($version->created_at->toISOString(), filamentUser()) }}
                            </div>

                            {{-- Creator Name --}}
                            @if ($version->creator?->name)
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    @if ($version->creator->name !== filamentUser()->name)
                                        {{ $version->creator->name }}
                                    @else
                                        {{ __('document.versions.you') }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Infinite Scroll Trigger --}}
                    @if ($hasMoreVersions)
                        <div x-intersect="$wire.loadMoreVersions()" class="flex items-center justify-center py-4">
                            @if ($isLoading)
                                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ __('document.versions.loading_more') }}
                                </div>
                            @else
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ __('document.versions.scroll_for_more') }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Content - Document Preview --}}
        <div class="flex flex-col flex-1">
            {{-- Content Header --}}
            <div
                class="flex items-center justify-between flex-shrink-0 px-4 py-3 border-b border-zinc-200 md:px-6 md:py-4 dark:border-zinc-700">
                <div class="flex items-center space-x-3">
                    {{-- Mobile Sidebar Toggle --}}
                    <button type="button" class="text-zinc-400 hover:text-zinc-600 md:hidden dark:hover:text-zinc-300"
                        wire:click="toggleSidebar" title="{{ __('document.versions.toggle_versions') }}">
                        @if ($sidebarCollapsed)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7"></path>
                            </svg>
                        @endif
                    </button>

                    @if ($this->selectedVersion)
                        <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('document.versions.preview_mode') }}</span>
                        <span class="hidden font-mono text-sm text-zinc-500 sm:inline dark:text-zinc-400">
                            {{ substr($this->selectedVersion->id, -6) }}
                        </span>
                        <span class="hidden text-sm text-zinc-500 lg:inline dark:text-zinc-400">
                            {{ $this->selectedVersion->created_at->format('M j, Y \a\t g:i A') }}
                        </span>
                    @endif
                </div>

                {{-- Main Close Button (always visible on all screen sizes) --}}
                <button type="button" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    wire:click="$dispatch('closeModal')" title="{{ __('document.versions.close') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            {{-- Document Content --}}
            <div class="flex-1 p-6 overflow-y-auto">
                @if (!$this->selectedVersion || empty($this->selectedVersion->content))
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-zinc-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $this->selectedVersion ? __('document.versions.no_content_available') : __('document.versions.select_version') }}
                            </h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $this->selectedVersion
                                    ? __('document.versions.version_empty')
                                    : __('document.versions.choose_version_preview') }}
                            </p>
                        </div>
                    </div>
                @else
                    {{-- Document Editor (Read-Only) --}}
                    <div wire:key="editor-{{ $selectedVersionId }}" x-load
                        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('documentEditor') }}"
                        x-data="documentEditor(@js($this->selectedVersion->content), '', @js(false), null, 0, '', @js($this->getFullToolsConfig()), @js($this->getAllowedTools()))" class="w-full max-w-4xl mx-auto">

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
