<?php

declare(strict_types=1);

namespace App\Livewire;

use App\ValueObjects\CommentTime;
use Livewire\Component;

final class PreviewAudio extends Component
{
    public $comments = [];

    public $audioUrl = '';

    public $config = [];

    public function mount()
    {
        // Local audio file from storage
        $this->audioUrl = asset('storage/audio/audio.mp3');

        // Sample comments data - created using time-precise timing
        $sampleRate = 44100; // Standard audio sample rate
        $precision = 0.01; // 10ms precision for audio

        $comment1Time = CommentTime::fromSeconds(3.0);
        $comment2Time = CommentTime::fromSeconds(10.0);
        $comment3Time = CommentTime::fromSeconds(20.0);

        $this->comments = [
            [
                'commentId' => 1,
                'avatar' => 'https://ui-avatars.com/api/?name=Audio+Producer&background=3b82f6&color=fff',
                'name' => 'Audio Producer',
                'body' => 'Great bell sound quality. Clear and crisp.',
                'timestamp' => $comment1Time->asSeconds(),
                'precision' => $precision,
            ],
            [
                'commentId' => 2,
                'avatar' => 'https://ui-avatars.com/api/?name=Sound+Engineer&background=ef4444&color=fff',
                'name' => 'Sound Engineer',
                'body' => 'The reverb tail could be longer here.',
                'timestamp' => $comment2Time->asSeconds(),
                'precision' => $precision,
            ],
            [
                'commentId' => 3,
                'avatar' => 'https://ui-avatars.com/api/?name=Music+Director&background=10b981&color=fff',
                'name' => 'Music Director',
                'body' => 'Perfect timing for the musical cue.',
                'timestamp' => $comment3Time->asSeconds(),
                'precision' => $precision,
            ],
        ];

        // Configuration for audio annotation features
        $this->config = [
            'features' => [
                'enableAnnotations' => true,
                'enableComments' => true,
                'enableRegions' => true,
                'enableKeyboardShortcuts' => true,
                'enableWaveformClick' => true,
                'enableVolumeControls' => true,
                'enableTimeDisplay' => true,
            ],
            'ui' => [
                'waveColor' => '#e5e7eb',
                'progressColor' => '#3b82f6',
                'cursorColor' => '#ef4444',
                'theme' => 'auto',
                'progressBarMode' => 'always',
            ],
            'annotations' => [
                'enableWaveformComments' => true,
                'enableHapticFeedback' => true,
                'regionColor' => 'rgba(59, 130, 246, 0.2)',
                'regionBorderColor' => '#3b82f6',
            ],
            'timing' => [
                'commentPrecision' => 0.01, // 10ms precision
                'seekPrecision' => 0.1, // 100ms for keyboard seeking
            ],
        ];
    }

    public function addComment($timestamp, $precision = null)
    {
        $time = CommentTime::fromSeconds($timestamp);

        // Validate timestamp
        if (! is_numeric($timestamp) || $timestamp < 0) {
            session()->flash('error', 'Invalid timestamp provided');

            return;
        }

        // Apply precision alignment if provided
        if ($precision > 0) {
            $alignedSeconds = round($timestamp / $precision) * $precision;
            $time = CommentTime::fromSeconds($alignedSeconds);
            $timestamp = $time->asSeconds();
        }

        // Simulate adding a new comment
        $this->comments[] = [
            'commentId' => count($this->comments) + 1,
            'avatar' => 'https://ui-avatars.com/api/?name=New+User&background=8b5cf6&color=fff',
            'name' => 'New User',
            'body' => "New audio comment added at {$time->display()}",
            'timestamp' => $timestamp,
            'precision' => $precision ?: 0.01,
        ];

        // Dispatch event to update the frontend
        $this->dispatch('commentsUpdated', comments: $this->comments);

        // Success message
        session()->flash('message', "Audio comment added at {$time->display()}");
    }

    public function loadComment($commentId)
    {
        $comment = collect($this->comments)->firstWhere('commentId', $commentId);

        if ($comment) {
            // Simulate loading comment details
            session()->flash('message', "Loaded audio comment: {$comment['body']}");

            // Dispatch events for frontend handling
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
        $this->config['features']['enableRegions'] = $enabled;
        $this->config['annotations']['enableWaveformComments'] = $enabled;
        $this->config['annotations']['enableHapticFeedback'] = $enabled;
    }

    public function updateAudioSource($url): void
    {
        // Validate URL format
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            session()->flash('error', 'Invalid audio URL provided');

            return;
        }

        $this->audioUrl = $url;
        session()->flash('message', 'Audio source updated successfully');
    }

    public function render()
    {
        return view('livewire.preview-audio');
    }
}
