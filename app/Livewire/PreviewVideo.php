<?php

declare(strict_types=1);

namespace App\Livewire;

use App\ValueObjects\CommentTime;
use Livewire\Component;

final class PreviewVideo extends Component
{
    public $comments = [];

    public $regions = [];

    public $videoUrl = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4';

    public $qualitySources = [];

    public $config = [];

    public function mount()
    {
        // Google Cloud Storage videos with proper CORS headers
        $this->qualitySources = [
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'type' => 'video/mp4',
                'label' => '1080p',
                'quality' => '1080',
                'selected' => true, // Default - highest quality
            ],
            [
                'src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                'type' => 'video/mp4',
                'label' => '720p',
                'quality' => '720',
                'selected' => false,
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

        // Sample comments data - created using frame-precise timing
        $frameRate = 30.0; // Default 30fps - will be dynamically set based on actual video

        $comment1Time = CommentTime::fromFrame(150, $frameRate); // Frame 150 (5 seconds)
        $comment2Time = CommentTime::fromFrame(450, $frameRate); // Frame 450 (15 seconds)
        $comment3Time = CommentTime::fromFrame(1350, $frameRate); // Frame 1350 (45 seconds)
        $comment4Time = CommentTime::fromFrame(2460, $frameRate); // Frame 2460 (82 seconds)

        $this->comments = [
            [
                'commentId' => 1,
                'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff',
                'name' => 'John Doe',
                'body' => 'Great scene! The animation quality is impressive here.',
                'timestamp' => $comment1Time->asSeconds(),
                'frameNumber' => $comment1Time->getFrame($frameRate),
                'frameRate' => $frameRate,
            ],
            [
                'commentId' => 2,
                'avatar' => 'https://ui-avatars.com/api/?name=Jane+Smith&background=ef4444&color=fff',
                'name' => 'Jane Smith',
                'body' => 'Love the character design. The attention to detail is amazing.',
                'timestamp' => $comment2Time->asSeconds(),
                'frameNumber' => $comment2Time->getFrame($frameRate),
                'frameRate' => $frameRate,
            ],
            [
                'commentId' => 3,
                'avatar' => 'https://ui-avatars.com/api/?name=Mike+Wilson&background=10b981&color=fff',
                'name' => 'Mike Wilson',
                'body' => 'This part needs some work on the lighting.',
                'timestamp' => $comment3Time->asSeconds(),
                'frameNumber' => $comment3Time->getFrame($frameRate),
                'frameRate' => $frameRate,
            ],
            [
                'commentId' => 4,
                'avatar' => 'https://ui-avatars.com/api/?name=Alice+Cooper&background=9333ea&color=fff',
                'name' => 'Alice Cooper',
                'body' => 'Perfect timing for this action sequence!',
                'timestamp' => $comment4Time->asSeconds(),
                'frameNumber' => $comment4Time->getFrame($frameRate),
                'frameRate' => $frameRate,
            ],
        ];

        // Sample regions data - existing regions with indigo color and opacity for overlap
        $this->regions = [
            [
                'id' => 'region-1',
                'startTime' => 10.5,
                'endTime' => 25.3,
                'startFrame' => $this->getFrameNumber(10.5, $frameRate),
                'endFrame' => $this->getFrameNumber(25.3, $frameRate),
                'title' => 'Opening Scene',
                'description' => 'Character introduction and world building',
                'color' => '#6366f1', // Indigo-500
                'opacity' => 0.6,
                'temporary' => false,
            ],
            [
                'id' => 'region-2',
                'startTime' => 35.2,
                'endTime' => 48.7,
                'startFrame' => $this->getFrameNumber(35.2, $frameRate),
                'endFrame' => $this->getFrameNumber(48.7, $frameRate),
                'title' => 'Action Sequence',
                'description' => 'Main character encounters first challenge',
                'color' => '#6366f1', // Indigo-500
                'opacity' => 0.6,
                'temporary' => false,
            ],
            [
                'id' => 'region-3',
                'startTime' => 42.1, // Intentionally overlaps with region-2
                'endTime' => 58.9,
                'startFrame' => $this->getFrameNumber(42.1, $frameRate),
                'endFrame' => $this->getFrameNumber(58.9, $frameRate),
                'title' => 'Overlapping Scene',
                'description' => 'Secondary plot development during action',
                'color' => '#6366f1', // Indigo-500
                'opacity' => 0.5, // Slightly more transparent for overlap
                'temporary' => false,
            ],
            [
                'id' => 'region-4',
                'startTime' => 72.8,
                'endTime' => 91.4,
                'startFrame' => $this->getFrameNumber(72.8, $frameRate),
                'endFrame' => $this->getFrameNumber(91.4, $frameRate),
                'title' => 'Resolution',
                'description' => 'Conflict resolution and character growth',
                'color' => '#6366f1', // Indigo-500
                'opacity' => 0.6,
                'temporary' => false,
            ],
            [
                'id' => 'region-5',
                'startTime' => 95.0,
                'endTime' => 120.0,
                'startFrame' => $this->getFrameNumber(95.0, $frameRate),
                'endFrame' => $this->getFrameNumber(120.0, $frameRate),
                'title' => 'Closing Credits',
                'description' => 'End sequence and credits roll',
                'color' => '#6366f1', // Indigo-500
                'opacity' => 0.6,
                'temporary' => false,
            ],
        ];

        // Example configuration - can be customized based on use case
        $this->config = [
            'video' => [
                'frameRate' => $frameRate,
            ],
            'mode' => [
                'viewOnly' => false, // Set to true to disable comment and region creation
            ],
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
                'progressBarMode' => 'always',
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

    public function addComment(
        $timestamp,
        $frameNumber = null,
        $frameRate = null,
    ) {
        $time = CommentTime::fromSeconds($timestamp);

        // Validate timestamp
        if (! is_numeric($timestamp) || $timestamp < 0) {
            session()->flash('error', 'Invalid timestamp provided');

            return;
        }

        // If frame rate is provided, ensure frame alignment
        if ($frameRate > 0) {
            $time = $time->getFrameAlignedTime($frameRate);
            $frameNumber = $time->getFrame($frameRate);
            $timestamp = $time->asSeconds(); // Use frame-aligned timestamp
        }

        // Calculate frame number if not provided but frame rate is available
        if ($frameNumber === null && $frameRate > 0) {
            $frameNumber = $time->getFrame($frameRate);
        }

        // Simulate adding a new comment
        $this->comments[] = [
            'commentId' => count($this->comments) + 1,
            'avatar' => 'https://ui-avatars.com/api/?name=New+User&background=8b5cf6&color=fff',
            'name' => 'New User',
            'body' => $frameRate > 0
                ? "New comment added at {$time->displayWithFrame($frameRate)}"
                : "New comment added at {$time->display()}",
            'timestamp' => $timestamp,
            'frameNumber' => $frameNumber,
            'frameRate' => $frameRate,
        ];

        // Dispatch event to update the frontend
        $this->dispatch('commentsUpdated', comments: $this->comments);

        // Success message with frame info
        $successMessage = $frameRate > 0
            ? "Comment added at {$time->displayWithFrame($frameRate)}"
            : "Comment added at {$time->display()}";

        session()->flash('message', $successMessage);
    }

    public function loadComment($commentId)
    {
        $comment = collect($this->comments)
            ->firstWhere('commentId', $commentId);

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
        $this->config['features']['enableAnnotations'] =
            ! $this->config['features']['enableAnnotations'];

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

    /**
     * Helper method to calculate frame number from timestamp.
     */
    private function getFrameNumber(float $timestamp, float $frameRate): int
    {
        return (int) floor($timestamp * $frameRate) + 1;
    }
}
