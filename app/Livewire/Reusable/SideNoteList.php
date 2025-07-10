<?php

declare(strict_types=1);

namespace App\Livewire\Reusable;

use App\Contracts\Sidenoteable;
use App\Models\SideNote;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Collection $notes
 */
final class SideNoteList extends Component
{
    #[Locked]
    public Sidenoteable $sidenoteable;

    public function mount(Sidenoteable $sidenoteable)
    {
        $this->sidenoteable = $sidenoteable;
    }

    #[Computed]
    public function notes()
    {
        return $this->sidenoteable->getSideNotesBy(filamentUser());
    }

    public function addNote(string $content)
    {
        if (blank($content)) {
            return;
        }
        $sideNote = new SideNote([
            'content' => $content,
        ]);
        $sideNote->creator()->associate(filamentUser());
        $this->sidenoteable->addSideNote($sideNote);
        unset($this->notes);
    }

    public function deleteNote(string|int $noteId)
    {
        $sideNote = $this->sidenoteable->getSideNote($noteId);
        $this->authorize('delete', $sideNote);
        $this->sidenoteable->deleteSideNote($sideNote);
        unset($this->notes);
    }

    public function render()
    {
        return view('livewire.reusable.side-note-list');
    }
}
