<?php

declare(strict_types=1);

namespace App\Livewire\Reusable;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read bool $canAcceptNotes
 * @property-read array $voiceNoteUrls
 */
final class VoiceNoteComponent extends Component
{
    public bool $uploading = false;

    public bool $hasRecording = false;

    public ?array $voiceNoteUrls = [];

    public function mount()
    {

        $this->uploading = false;
        $this->hasRecording = false;
    }

    #[Computed]
    public function voiceNoteUrls(): array
    {
        return $this->voiceNoteUrls;
    }

    #[Computed]
    public function canAcceptNotes(): bool
    {
        return count($this->voiceNoteUrls) < 3;
    }

    public function addVoiceNote($audioBlob)
    {
        // Check if we already have 3 voice notes
        if (count($this->voiceNoteUrls) >= 3) {
            return false;
        }

        $this->uploading = true;

        // Simulate upload progress with sleep
        sleep(2);

        // For demo purposes, use local audio file
        $newUrl = Storage::url('audio/audio.mp3');

        $this->voiceNoteUrls[] = $newUrl;
        $this->hasRecording = true;
        $this->uploading = false;

        // Dispatch event to notify parent component
        $this->dispatch('voice-note:uploaded', $newUrl);

        return true;
    }

    public function removeVoiceNote($index)
    {
        if (isset($this->voiceNoteUrls[$index])) {
            array_splice($this->voiceNoteUrls, $index, 1);
            $this->voiceNoteUrls = array_values($this->voiceNoteUrls); // Reindex array
        }

        $this->hasRecording = ! empty($this->voiceNoteUrls);

        // Dispatch event to notify parent component
        $this->dispatch('voice-note:removed', $index);
    }

    public function canAddMore()
    {
        return count($this->voiceNoteUrls) < 3;
    }

    public function render()
    {
        return view('livewire.reusable.voice-note-component');
    }
}
