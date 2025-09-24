<?php

declare(strict_types=1);

namespace App\Livewire\Reusable;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read bool $canAcceptNotes
 * @property-read array $voiceNoteUrls
 * @property-read array  $getNoteUrls
 */
final class VoiceNoteComponent extends Component
{
    public bool $hasRecording = false;

    public array $voiceNoteUrls = [];

    #[Locked]
    public int $maxNotes = 1;

    #[Locked]
    public int $maxDurationInSeconds = 30;

    public string $recorderKey = '';

    public function mount()
    {
        $this->hasRecording = false;
        $this->recorderKey = 'recorder_' . uniqid();
    }

    // #[Computed]
    // public function voiceNoteUrls(): array
    // {
    //     return $this->voiceNoteUrls;
    // }

    #[Computed]
    public function canAcceptNotes(): bool
    {
        return count($this->voiceNoteUrls) < $this->maxNotes;
    }

    #[Computed]
    public function getNotesUrls(): array
    {
        return $this->voiceNoteUrls;
    }

    #[On('voice-recorder:voice-uploaded')]
    public function onVoiceUploaded($url)
    {
        logger()->info('VoiceNote received voice-recorder:voice-uploaded event', [
            'url' => $url,
        ]);

        $this->voiceNoteUrls[] = $url;
        $this->hasRecording = !empty($this->voiceNoteUrls);

        logger()->info('VoiceNote updated URLs', ['voiceNoteUrls' => $this->voiceNoteUrls]);

        unset($this->getNotesUrls);

        // Fire event to parent components
        $this->dispatch('voice-note:uploaded', $url);
    }

    #[On('voice-note:canceled')]
    public function clear()
    {
        // Delete all uploaded files from storage before clearing the array
        foreach ($this->voiceNoteUrls as $url) {
            // Extract the file path from the URL and delete from storage
            // URL format: /storage/voice-notes/filename.webm
            $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                logger()->info('Deleted voice note file during clear', [
                    'path' => $path,
                ]);
            }
        }

        $this->reset('voiceNoteUrls');
        // Generate new key to force recorder reinitialization
        $this->recorderKey = 'recorder_' . uniqid();
    }

    public function removeVoiceNote($index)
    {
        if (isset($this->voiceNoteUrls[$index])) {
            $url = $this->voiceNoteUrls[$index];
            $playerKey = 'voice-note-' . $this->getUniqueVoiceNoteKey($url);

            // Dispatch event to destroy the specific audio player instance
            $this->dispatch('destroy-audio-player', playerKey: $playerKey);

            // Extract the file path from the URL and delete from storage
            // URL format: /storage/voice-notes/filename.webm
            $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                logger()->info('Deleted voice note file', ['path' => $path]);
            }

            // Remove from array
            array_splice($this->voiceNoteUrls, $index, 1);
            $this->voiceNoteUrls = array_values($this->voiceNoteUrls); // Reindex array
        }

        $this->hasRecording = !empty($this->voiceNoteUrls);

        // Dispatch event to notify parent component
        $this->dispatch('voice-note:removed', $index);
    }

    public function canAddMore(): bool
    {
        return count($this->voiceNoteUrls) < $this->maxNotes;
    }

    public function render()
    {
        return view('livewire.reusable.voice-note-component');
    }

    private function getUniqueVoiceNoteKey(string $url): string
    {
        return md5($url);
    }
}
