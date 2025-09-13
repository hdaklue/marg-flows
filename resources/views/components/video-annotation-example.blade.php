{{-- Example usage of the video annotation component --}}

<div>
    <h2 class="text-xl font-semibold mb-4">Video Annotation Example</h2>
    
    <x-video-annotaion.index 
        video-src="{{ $videoUrl ?? 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4' }}"
        :comments="$comments ?? '[]'"
        :on-comment="'handleComment'"
    />
</div>

@push('scripts')
<script>
// Example of handling comment events in a Livewire component
document.addEventListener('alpine:init', () => {
    // Listen for add comment events
    document.addEventListener('addComment', function(event) {
        console.log('Add comment at:', event.detail);
        
        // Example: Send to Livewire component
        // @this.call('addComment', event.detail.timestamp);
        
        // Or make direct API call
        // fetch('/api/comments', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({
        //         timestamp: event.detail.timestamp,
        //         video_id: {{ $videoId ?? 1 }}
        //     })
        // });
    });

    // Listen for load comment events
    document.addEventListener('loadComment', function(event) {
        console.log('Load comment:', event.detail.commentId);
        
        // Example: Send to Livewire component
        // @this.call('loadComment', event.detail.commentId);
    });
});

// Example callback function for onComment prop
function handleComment(action, data) {
    console.log('Comment action:', action, data);
    
    if (action === 'addComment') {
        // Handle add comment
        console.log('Adding comment at timestamp:', data.timestamp);
    } else if (action === 'loadComment') {
        // Handle load comment
        console.log('Loading comment ID:', data.commentId);
    }
}
</script>
@endpush