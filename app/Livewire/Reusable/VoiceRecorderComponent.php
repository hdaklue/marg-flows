<?php

declare(strict_types=1);

namespace App\Livewire\Reusable;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

final class VoiceRecorderComponent extends Component
{
    use WithFileUploads;

    public string $instanceKey = '';

    public bool $outlined = false;

    public string $size = 'sm';

    public ?int $maxDuration = null;

    public int $maxDurationInSeconds;

    public $audio;

    public function mount()
    {
        $this->maxDurationInSeconds = $this->maxDuration ?? config(
            'voice-notes.maxDuration',
        );
        $this->instanceKey = 'recorder_' . uniqid();
    }

    public function finalizeNoteUpload($tempFilename)
    {
        // Move the temporary uploaded file to permanent storage
        $path = $this->audio->storeAs(
            'voice-notes',
            'voice-note-' . time() . '.webm',
            'public',
        );
        $url = Storage::url($path);
        $this->audio->delete();

        // Clear the audio property to clean up temporary file
        $this->audio = null;

        logger()->info('VoiceRecorder moved file to permanent storage', [
            'path' => $path,
            'url' => $url,
        ]);
        $this->dispatch('voice-recorder:voice-uploaded', url: $url);
    }

    #[On('voice-note:canceled')]
    public function clear()
    {
        // Regenerate key to force fresh state
        $this->instanceKey = 'recorder_' . uniqid();
    }

    public function render()
    {
        return view('livewire.reusable.voice-recorder-component');
    }
}
