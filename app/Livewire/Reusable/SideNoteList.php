<?php

namespace App\Livewire\Reusable;

use Livewire\Component;
use App\Models\SideNote;
use Livewire\Attributes\Lazy;
use App\Contracts\Sidenoteable;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;


class SideNoteList extends Component
{
    #[Locked]
    public Sidenoteable $sidenoteable;

    public function mount($sidenoteable)
    {
        $this->sidenoteable = $sidenoteable;
    }

    public function loadNotes()
    {

    }

    #[Computed]
    public function notes()
    {
        return SideNote::for($this->sidenoteable, filamentUser())->get();
    }

    public function addNote(string $content)
    {
        if(blank($content))
        {
            return;
        }
        $sideNote = SideNote::make([
            'content' => $content
        ]);
        $sideNote->creator()->associate(filamentUser());
        $this->sidenoteable->sideNotes()->save($sideNote);
        unset($this->notes);
    }


    
    public function render()
    {
        return view('livewire.reusable.side-note-list');
    }
}
