<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\DTOs\EditorJS\EditorJSDocumentDto;
use App\DTOs\Document\DocumentWithBlocksDto;
use App\Facades\DocumentManager;
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

    public DocumentWithBlocksDto $page;

    public string $content;

    public function mount(string $pageId, $canEdit = true)
    {
        // Get the document model for authorization
        $document = DocumentManager::getDocument($pageId);
        $this->authorize('view', $document);

        // Get the DTO with blocks for editing
        $this->page = DocumentManager::getDocumentWithBlocks($pageId);
        $this->content = $this->page->getBlocksJson();
        $this->canEdit = $canEdit;
    }

    #[Computed]
    public function updatedAtString(): string
    {
        return $this->page->getUpdatedAtString();
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
        // Get the underlying Document model for authorization and updates
        $document = DocumentManager::getDocument($this->page->id);
        $this->authorize('update', $document);

        try {
            // Parse the JSON content
            $editorData = json_decode($content, true);

            // Use DocumentManager to update the document
            DocumentManager::update($document, ['blocks' => $editorData]);

            // Update the DTO with new content
            $this->page->updateBlocksFromJson($content);
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
