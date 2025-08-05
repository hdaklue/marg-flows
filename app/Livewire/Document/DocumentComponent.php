<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\Models\Document;
use Exception;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Log;

/**
 * @property-read string  $updatedAtString
 * @property-read array $participantsArray;
 */
final class DocumentComponent extends Component
{
    public $canEdit = true;

    public Document $page;

    public string $content;

    public function mount(string $pageId, $canEdit = true)
    {

        $this->page = Document::where('id', $pageId)->firstOrFail();
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

    #[Computed(true)]
    public function userPermissions(): array
    {
        return [
            'canManageMembers' => filamentUser()->can('manageMembers', $this->page),
            'canEdit' => filamentUser()->can('update', $this->page),
        ];
    }

    #[Computed]
    public function participants(): Collection
    {
        return $this->page->getParticipants();
    }

    #[Computed]
    public function participantsArray(): array
    {

        return $this->participants->asDtoArray()->toArray();
    }

    #[On('roleable-entity:members-updated.{page.id}')]
    public function reloadPartipants(): void
    {
        unset($this->participants, $this->participantsArray);

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
        return view('livewire.page.document-component');
    }
}
