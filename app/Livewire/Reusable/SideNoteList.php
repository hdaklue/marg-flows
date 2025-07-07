<?php

declare(strict_types=1);

namespace App\Livewire\Reusable;

use App\Contracts\Sidenoteable;
use App\Models\SideNote;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SideNoteList extends Component
{
    #[Locked]
    public Sidenoteable $sidenoteable;

    public function mount($sidenoteable)
    {
        $this->sidenoteable = $sidenoteable;
    }

    public function loadNotes() {}

    #[Computed]
    public function notes()
    {
        return SideNote::for($this->sidenoteable, filamentUser())->latest()->get();
    }

    public function addNote(string $content)
    {
        if (blank($content)) {
            return;
        }
        $sideNote = SideNote::make([
            'content' => $content,
        ]);
        $sideNote->creator()->associate(filamentUser());
        $this->sidenoteable->sideNotes()->save($sideNote);
        unset($this->notes);
    }

    public function deleteNote(string|int $noteId)
    {
        $note = SideNote::where('id', $noteId)->firstOrFail();

        $this->authorize('delete', $note);

        $note->delete();
        unset($this->notes);
    }

    public function render()
    {
        return view('livewire.reusable.side-note-list');
    }
}
