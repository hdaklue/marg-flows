<?php

namespace App\Livewire;

use Livewire\Component;

class PreviewVideo extends Component
{
    public $comments = [];
    public $videoUrl = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
    public $qualitySources = [];
    
    public function mount()
    {
        // Sample quality sources for the video
        $this->qualitySources = [
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'type' => 'video/mp4',
                'label' => '720p',
                'quality' => '720'
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'type' => 'video/mp4',
                'label' => '480p',
                'quality' => '480'
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'type' => 'video/mp4',
                'label' => '360p',
                'quality' => '360'
            ]
        ];

        // Sample comments data
        $this->comments = [
            [
                'commentId' => 1,
                'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff',
                'name' => 'John Doe',
                'body' => 'Great scene! The animation quality is impressive here.',
                'timestamp' => 15000 // 15 seconds
            ],
            [
                'commentId' => 2,
                'avatar' => 'https://ui-avatars.com/api/?name=Jane+Smith&background=ef4444&color=fff',
                'name' => 'Jane Smith',
                'body' => 'Love the character design. The attention to detail is amazing.',
                'timestamp' => 45000 // 45 seconds
            ],
            [
                'commentId' => 3,
                'avatar' => 'https://ui-avatars.com/api/?name=Mike+Wilson&background=10b981&color=fff',
                'name' => 'Mike Wilson',
                'body' => 'This part needs some work on the lighting.',
                'timestamp' => 82000 // 1:22
            ]
        ];
    }

    public function addComment($timestamp)
    {
        // Simulate adding a new comment
        $this->comments[] = [
            'commentId' => count($this->comments) + 1,
            'avatar' => 'https://ui-avatars.com/api/?name=New+User&background=8b5cf6&color=fff',
            'name' => 'New User',
            'body' => 'New comment added at ' . ($timestamp / 1000) . ' seconds',
            'timestamp' => $timestamp
        ];

        // Dispatch event to update the frontend
        $this->dispatch('commentsUpdated', comments: $this->comments);
        
        // You can also make API calls here
        session()->flash('message', 'Comment added successfully!');
    }

    public function loadComment($commentId)
    {
        $comment = collect($this->comments)->firstWhere('commentId', $commentId);
        
        if ($comment) {
            // Simulate loading comment details
            session()->flash('message', "Loaded comment: {$comment['body']}");
            
            // You can dispatch events or make API calls here
            $this->dispatch('commentLoaded', comment: $comment);
        }
    }

    public function render()
    {
        return view('livewire.preview-video');
    }
}
