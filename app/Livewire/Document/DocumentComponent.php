<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\Facades\DocumentManager;
use App\Models\Document;
use App\Services\Document\Facades\DocumentBuilder;
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

    public array $content;

    public string $userPlan = 'advanced'; // Default plan

    public function mount(string $pageId, $canEdit = true)
    {

        $document = DocumentManager::getDocument($pageId);

        // Set the page property
        $this->page = $document;

        // Initialize resolver using facade
        $this->content = $document->getAttribute('blocks');
        $this->canEdit = $canEdit;
    }

    public function getToolsConfig()
    {
        return match ($this->userPlan) {
            'simple' => DocumentBuilder::simple()->build(),
            'advanced' => DocumentBuilder::advanced()->build(),
        };
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

            unset($this->updatedAtString);

        } catch (Exception $e) {
            Log::error('Document save failed', [
                'page_id' => $this->page->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // /**
    //  * Get allowed block types for frontend editor configuration.
    //  */
    // #[Computed]
    // public function allowedBlockTypes(): array
    // {
    //     return $this->documentResolver->getAllowedBlockTypes(
    //         strtolower($this->userPlan),
    //     );
    // }

    // /**
    //  * Check if user can use specific block type.
    //  */
    // public function canUseBlockType(string $blockType): bool
    // {
    //     return $this->documentResolver->isBlockTypeAllowed(
    //         $blockType,
    //         strtolower($this->userPlan),
    //     );
    // }

    public function render()
    {
        return view('livewire.page.document-component');
    }

    // /**
    //  * Get user's plan - implement based on your auth/tenant system.
    //  */
    // private function getUserPlan(): string
    // {
    //     // Example implementation - adjust based on your system
    //     return auth()->user()?->currentTenant?->plan ?? 'simple';
    // }

    // /**
    //  * Get filtered blocks as JSON based on user plan.
    //  */
    // private function getFilteredBlocksJson(): string
    // {
    //     // Filter blocks based on user plan (blocks is now guaranteed to be DocumentBlocksCollection)
    //     $filteredBlocks = $this->documentResolver->filter(
    //         $this->page->blocks,
    //         strtolower($this->userPlan),
    //     );

    //     // Return as EditorJS JSON format
    //     return $filteredBlocks->toEditorJson();
    // }
}
