<?php

declare(strict_types=1);

namespace App\Livewire\Page;

use App\Models\Page;
use Exception;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Log;

/**
 * @property-read string  $updatedAtString
 */
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

    #[Computed]
    public function updatedAtString(): string
    {
        return $this->page->updated_at->diffForHumans();
    }

    public function getUpdatedAtString(): string
    {
        return $this->updatedAtString;
    }

    public function saveDocument(string $content)
    {
        $this->authorize('update', $this->page);

        try {
            // Parse the JSON content
            $blocks = json_decode($content, true);

            // Update the page (JSON cast handles the conversion)
            $this->page->update(['blocks' => $blocks]);

            // Update local content
            $this->content = $content;

            unset($this->updatedAtString);

        } catch (Exception $e) {
            Log::error('Document save failed', [
                'page_id' => $this->page->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.page.document');
    }
}
