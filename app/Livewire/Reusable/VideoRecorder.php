<?php

declare(strict_types=1);

namespace App\Livewire\Reusable;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read bool $canAcceptVideos
 * @property-read array $videoUrls
 */
final class VideoRecorder extends Component
{
    public bool $uploading = false;

    public bool $hasRecording = false;

    private array $videoUrls = [];

    public function mount()
    {
        $this->videoUrls = [];
        $this->uploading = false;
        $this->hasRecording = false;
    }

    #[Computed]
    public function videoUrls(): array
    {
        return $this->videoUrls;
    }

    #[Computed]
    public function canAcceptVideos(): bool
    {
        return count($this->videoUrls) < 3;
    }

    public function addVideo($videoBlob)
    {
        // Check if we already have 3 videos
        if (count($this->videoUrls) >= 3) {
            return false;
        }

        $this->uploading = true;

        // Simulate upload progress with sleep
        sleep(2);

        // For demo purposes, use local video file
        $newUrl = Storage::url('videos/sample.mp4');

        $this->videoUrls[] = $newUrl;
        $this->hasRecording = true;
        $this->uploading = false;

        // Dispatch event to notify parent component
        $this->dispatch('video:uploaded', $newUrl);

        return true;
    }

    public function removeVideo($index)
    {
        if (isset($this->videoUrls[$index])) {
            array_splice($this->videoUrls, $index, 1);
            $this->videoUrls = array_values($this->videoUrls); // Reindex array
        }

        $this->hasRecording = ! empty($this->videoUrls);

        // Dispatch event to notify parent component
        $this->dispatch('video:removed', $index);
    }

    public function render()
    {
        return view('livewire.reusable.video-recorder');
    }
}
