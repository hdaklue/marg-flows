{{-- Video Upload Block Final Render --}}
@if (!empty($url))
    <div class="{{ $containerClass }}">
        <video{{ $attributesString }}>
            <source src="{{ $url }}" type="{{ $mimeType }}">
            <p>Your browser doesn't support HTML5 video. <a href="{{ $url }}">Download the video</a> instead.</p>
        </video>
        
        @if (!empty($caption))
            <div class="mt-2 text-sm text-center text-zinc-600 dark:text-zinc-400">{{ $caption }}</div>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof videojs !== "undefined") {
                try {
                    const player = videojs("{{ $videoId }}", {
                        responsive: true,
                        fluid: {{ $fluid ? 'true' : 'false' }},
                        controls: true,
                        preload: "metadata"
                    });

                    player.ready(function() {
                        console.log("Video.js player ready for: {{ $videoId }}");
                    });
                } catch (error) {
                    console.warn("Video.js initialization failed:", error);
                }
            }
        });
    </script>
    @endpush
@endif