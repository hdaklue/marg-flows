<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

final class PreviewVideo extends Component
{
    public $comments = [];

    public $videoUrl = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4';

    public $qualitySources = [];

    public $config = [];

    public function mount()
    {
        // Reliable test video sources with different URLs (simulating different resolutions)
        $this->qualitySources = [
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'type' => 'video/mp4',
                'label' => '1080p',
                'quality' => '1080',
                'selected' => false,
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                'type' => 'video/mp4',
                'label' => '720p',
                'quality' => '720',
                'selected' => true,  // Default selection
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'type' => 'video/mp4',
                'label' => '480p',
                'quality' => '480',
                'selected' => false,
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                'type' => 'video/mp4',
                'label' => '360p',
                'quality' => '360',
                'selected' => false,
            ],
        ];

        // Sample comments data
        $this->comments = [
            [
                'commentId' => 1,
                'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff',
                'name' => 'John Doe',
                'body' => 'Great scene! The animation quality is impressive here.',
                'timestamp' => 15000, // 15 seconds
            ],
            [
                'commentId' => 2,
                'avatar' => 'https://ui-avatars.com/api/?name=Jane+Smith&background=ef4444&color=fff',
                'name' => 'Jane Smith',
                'body' => 'Love the character design. The attention to detail is amazing.',
                'timestamp' => 45000, // 45 seconds
            ],
            [
                'commentId' => 3,
                'avatar' => 'https://ui-avatars.com/api/?name=Mike+Wilson&background=10b981&color=fff',
                'name' => 'Mike Wilson',
                'body' => 'This part needs some work on the lighting.',
                'timestamp' => 82000, // 1:22
            ],
        ];

        // Example configuration - can be customized based on use case
        $this->config = [
            'features' => [
                'enableAnnotations' => true,
                'enableComments' => true,
                'enableProgressBarAnnotations' => true,
                'enableVideoAnnotations' => true,
                'enableResolutionSelector' => true,
                'enableVolumeControls' => true,
                'enableFullscreenButton' => true,
                'enableSettingsMenu' => true,
            ],
            'ui' => [
                'progressBarMode' => 'auto-hide',
                'showControls' => true,
                'helpTooltipLimit' => 3,
                'theme' => 'auto',
            ],
            'annotations' => [
                'showCommentsOnProgressBar' => true,
                'enableProgressBarComments' => true,
                'enableVideoComments' => true,
                'enableContextMenu' => true,
                'enableHapticFeedback' => true,
            ],
            'timing' => [
                'progressBarAutoHideDelay' => 2000,
                'progressBarHoverHideDelay' => 1000,
                'longPressDuration' => 500,
                'playPauseOverlayDuration' => 800,
                'helpTooltipDuration' => 3000,
            ],
        ];
    }

    public function addComment($timestamp)
    {
        // Validate timestamp
        if (! is_numeric($timestamp) || $timestamp < 0) {
            session()->flash('error', 'Invalid timestamp provided');

            return;
        }

        // Simulate adding a new comment
        $this->comments[] = [
            'commentId' => count($this->comments) + 1,
            'avatar' => 'https://ui-avatars.com/api/?name=New+User&background=8b5cf6&color=fff',
            'name' => 'New User',
            'body' => 'New comment added at ' . round($timestamp / 1000, 1) . ' seconds',
            'timestamp' => $timestamp,
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

    public function toggleAnnotations(): void
    {
        // Toggle the main annotation setting
        $this->config['features']['enableAnnotations'] = ! $this->config['features']['enableAnnotations'];

        // Update all related annotation features based on the main toggle
        $enabled = (bool) $this->config['features']['enableAnnotations'];

        $this->config['features']['enableComments'] = $enabled;
        $this->config['features']['enableProgressBarAnnotations'] = $enabled;
        $this->config['features']['enableVideoAnnotations'] = $enabled;
        $this->config['annotations']['showCommentsOnProgressBar'] = $enabled;
        $this->config['annotations']['enableProgressBarComments'] = $enabled;
        $this->config['annotations']['enableVideoComments'] = $enabled;
        $this->config['annotations']['enableContextMenu'] = $enabled;
    }

    public function render()
    {
        return view('livewire.preview-video');
    }
}
