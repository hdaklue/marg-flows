<?php

namespace App\Livewire;

use Livewire\Component;

class PreviewVideo extends Component
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
                'selected' => false
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                'type' => 'video/mp4',
                'label' => '720p',
                'quality' => '720',
                'selected' => true  // Default selection
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'type' => 'video/mp4',
                'label' => '480p',
                'quality' => '480',
                'selected' => false
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                'type' => 'video/mp4',
                'label' => '360p',
                'quality' => '360',
                'selected' => false
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
                'enableSettingsMenu' => true
            ],
            'ui' => [
                'progressBarMode' => 'auto-hide',
                'showControls' => true,
                'helpTooltipLimit' => 3,
                'theme' => 'auto'
            ],
            'annotations' => [
                'showCommentsOnProgressBar' => true,
                'enableProgressBarComments' => true,
                'enableVideoComments' => true,
                'enableContextMenu' => true,
                'enableHapticFeedback' => true
            ],
            'timing' => [
                'progressBarAutoHideDelay' => 2000,
                'progressBarHoverHideDelay' => 1000,
                'longPressDuration' => 500,
                'playPauseOverlayDuration' => 800,
                'helpTooltipDuration' => 3000
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

    public function toggleAnnotations()
    {
        // Example method to toggle annotations on/off
        $this->config['features']['enableAnnotations'] = !$this->config['features']['enableAnnotations'];
        
        // Update related annotation features
        if (!$this->config['features']['enableAnnotations']) {
            $this->config['features']['enableComments'] = false;
            $this->config['features']['enableProgressBarAnnotations'] = false;
            $this->config['features']['enableVideoAnnotations'] = false;
            $this->config['annotations']['showCommentsOnProgressBar'] = false;
            $this->config['annotations']['enableProgressBarComments'] = false;
            $this->config['annotations']['enableVideoComments'] = false;
        } else {
            $this->config['features']['enableComments'] = true;
            $this->config['features']['enableProgressBarAnnotations'] = true;
            $this->config['features']['enableVideoAnnotations'] = true;
            $this->config['annotations']['showCommentsOnProgressBar'] = true;
            $this->config['annotations']['enableProgressBarComments'] = true;
            $this->config['annotations']['enableVideoComments'] = true;
        }
        
        $this->ensureCompleteConfig();
    }

    public function setSimplePlayerMode()
    {
        // Configuration for simple video player without annotations
        $this->config = [
            'features' => [
                'enableAnnotations' => false,
                'enableComments' => false,
                'enableProgressBarAnnotations' => false,
                'enableVideoAnnotations' => false,
                'enableResolutionSelector' => true,
                'enableVolumeControls' => true,
                'enableFullscreenButton' => true,
                'enableSettingsMenu' => false
            ],
            'ui' => [
                'progressBarMode' => 'always-visible',
                'showControls' => true,
                'helpTooltipLimit' => 0,
                'theme' => 'auto'
            ],
            'annotations' => [
                'showCommentsOnProgressBar' => false,
                'enableProgressBarComments' => false,
                'enableVideoComments' => false,
                'enableContextMenu' => false,
                'enableHapticFeedback' => false
            ],
            'timing' => [
                'progressBarAutoHideDelay' => 2000,
                'progressBarHoverHideDelay' => 1000,
                'longPressDuration' => 500,
                'playPauseOverlayDuration' => 800,
                'helpTooltipDuration' => 3000
            ]
        ];
        
        $this->ensureCompleteConfig();
    }

    public function setAdvancedAnnotationMode()
    {
        // Configuration for full annotation capabilities
        $this->config = [
            'features' => [
                'enableAnnotations' => true,
                'enableComments' => true,
                'enableProgressBarAnnotations' => true,
                'enableVideoAnnotations' => true,
                'enableResolutionSelector' => true,
                'enableVolumeControls' => true,
                'enableFullscreenButton' => true,
                'enableSettingsMenu' => true
            ],
            'ui' => [
                'progressBarMode' => 'auto-hide',
                'showControls' => true,
                'helpTooltipLimit' => 5,
                'theme' => 'auto'
            ],
            'annotations' => [
                'showCommentsOnProgressBar' => true,
                'enableProgressBarComments' => true,
                'enableVideoComments' => true,
                'enableContextMenu' => true,
                'enableHapticFeedback' => true
            ],
            'timing' => [
                'progressBarAutoHideDelay' => 1500,
                'progressBarHoverHideDelay' => 800,
                'longPressDuration' => 400,
                'playPauseOverlayDuration' => 600,
                'helpTooltipDuration' => 2000
            ]
        ];
        
        $this->ensureCompleteConfig();
    }

    private function ensureCompleteConfig()
    {
        // Ensure all required config sections exist with defaults
        $defaults = [
            'features' => [
                'enableAnnotations' => true,
                'enableComments' => true,
                'enableProgressBarAnnotations' => true,
                'enableVideoAnnotations' => true,
                'enableResolutionSelector' => true,
                'enableVolumeControls' => true,
                'enableFullscreenButton' => true,
                'enableSettingsMenu' => true
            ],
            'ui' => [
                'progressBarMode' => 'auto-hide',
                'showControls' => true,
                'helpTooltipLimit' => 3,
                'theme' => 'auto'
            ],
            'annotations' => [
                'showCommentsOnProgressBar' => true,
                'enableProgressBarComments' => true,
                'enableVideoComments' => true,
                'enableContextMenu' => true,
                'enableHapticFeedback' => true
            ],
            'timing' => [
                'progressBarAutoHideDelay' => 2000,
                'progressBarHoverHideDelay' => 1000,
                'longPressDuration' => 500,
                'playPauseOverlayDuration' => 800,
                'helpTooltipDuration' => 3000
            ]
        ];

        // Merge with existing config to fill any missing keys
        $this->config = array_merge_recursive($defaults, $this->config);
    }

    public function render()
    {
        // Ensure config is complete before rendering
        $this->ensureCompleteConfig();
        return view('livewire.preview-video');
    }
}
