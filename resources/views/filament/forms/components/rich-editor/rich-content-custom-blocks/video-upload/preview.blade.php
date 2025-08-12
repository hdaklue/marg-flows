{{-- Video Upload Block Preview --}}
@if (empty($url))
    <div class="rounded-lg bg-zinc-100 p-4 text-center dark:bg-zinc-800">
        <div class="text-zinc-500 dark:text-zinc-400">Video Upload Block</div>
        <div class="text-sm text-zinc-400 dark:text-zinc-500">No video selected</div>
    </div>
@else
    <div x-data="videoPlayer" 
         data-video-url="{{ $url }}" 
         data-thumbnail-url="{{ $thumbnail ?? '' }}"
         class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <!-- Thumbnail with play button (EditorJS style) -->
        <div class="relative aspect-video cursor-pointer" @click="openModal">
            @if (!empty($thumbnail))
                <img src="{{ $thumbnail }}" alt="Video thumbnail" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center bg-zinc-200 dark:bg-zinc-700">
                    <svg class="h-16 w-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif
            
            <!-- Play button overlay -->
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="rounded-full bg-black bg-opacity-60 p-4 transition-all hover:bg-opacity-80">
                    <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        @if (!empty($caption))
            <div class="p-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $caption }}</div>
        @endif
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('videoPlayer', () => ({
            videoUrl: '',
            thumbnailUrl: '',
            
            init() {
                this.videoUrl = this.$el.dataset.videoUrl;
                this.thumbnailUrl = this.$el.dataset.thumbnailUrl;
            },
            
            openModal() {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75';
                modal.style.backdropFilter = 'blur(2px)';
                
                const modalContent = document.createElement('div');
                modalContent.className = 'relative max-w-4xl w-full mx-4';
                
                const video = document.createElement('video');
                video.className = 'w-full h-auto rounded-lg';
                video.controls = true;
                video.preload = 'metadata';
                video.autoplay = true;
                
                if (this.thumbnailUrl) {
                    video.poster = this.thumbnailUrl;
                }
                
                const source = document.createElement('source');
                source.src = this.videoUrl;
                source.type = this.getVideoMimeType(this.videoUrl);
                video.appendChild(source);
                
                const closeBtn = document.createElement('button');
                closeBtn.className = 'absolute -top-12 right-0 text-white hover:text-zinc-300 transition-colors';
                closeBtn.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                
                modalContent.appendChild(video);
                modalContent.appendChild(closeBtn);
                modal.appendChild(modalContent);
                
                const closeModal = () => {
                    video.pause();
                    modal.remove();
                    document.removeEventListener('keydown', handleKeyDown);
                };
                
                const handleKeyDown = (e) => {
                    if (e.key === 'Escape') closeModal();
                };
                
                closeBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
                document.addEventListener('keydown', handleKeyDown);
                
                document.body.appendChild(modal);
                video.focus();
            },
            
            getVideoMimeType(url) {
                const extension = url.split('.').pop().toLowerCase();
                const types = {
                    'mp4': 'video/mp4',
                    'webm': 'video/webm',
                    'ogv': 'video/ogg',
                    'ogg': 'video/ogg',
                    'mov': 'video/quicktime',
                    'avi': 'video/x-msvideo',
                    'm4v': 'video/mp4'
                };
                return types[extension] || 'video/mp4';
            }
        }));
    });
    </script>
@endif
