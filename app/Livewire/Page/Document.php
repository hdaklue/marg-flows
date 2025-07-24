<?php

declare(strict_types=1);

namespace App\Livewire\Page;

use App\Models\Page;
use Livewire\Component;

final class Document extends Component
{
    public $canEdit = true;

    public Page $page;

    public string $content;

    public function mount(string $pageId, $canEdit = true)
    {
        $this->page = Page::where('id', $pageId)->firstOrFail();
        $this->content = is_array($this->page->blocks) 
            ? json_encode($this->page->blocks) 
            : $this->page->blocks;
        $this->canEdit = $canEdit;
    }

    public function render()
    {
        return view('livewire.page.document');
    }
}
