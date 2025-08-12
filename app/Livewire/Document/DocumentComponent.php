<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\DTOs\Document\DocumentDto;
use App\Facades\DocumentManager;
use App\Models\Document;
use App\Services\Document\Facades\EditorBuilder;
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

    public array $content;

    public string $userPlan = 'ultimate'; // Default plan - testing restrictions

    public string $documentId;

    private DocumentDto $documentDto;

    public function mount(string $documentId, $canEdit = true)
    {
        $this->documentDto = DocumentManager::getDocumentDto($documentId);

        $this->documentId = $documentId;
        // Set the page property

        // Initialize resolver using facade
        $this->content = $this->documentDto->blocks;
        $this->canEdit = $canEdit;
    }

    #[Computed]
    public function document(): Document
    {
        return DocumentManager::getDocument($this->documentId);
    }

    public function getToolsConfig()
    {
        return match ($this->userPlan) {
            'simple' => EditorBuilder::simple()->build(),
            'advanced' => EditorBuilder::advanced()->build(),
            'ultimate' => EditorBuilder::ultimate()->build(),
            default => throw new Exception('Unable to resolve user plan'),
        };
    }

    public function getDocumentableKey(): string
    {
        return $this->document->documentable->getKey();
    }

    public function getDocumentableType(): string
    {
        return $this->document->documentable->getMorphClass();
    }

    /**
     * Get full tools config for rendering all existing blocks regardless of plan.
     * This ensures backward compatibility with blocks created on higher plans.
     */
    public function getFullToolsConfig()
    {
        // Start with base configuration to render all existing blocks
        $baseConfig = EditorBuilder::base()->build();

        // Override with current plan-specific configurations
        $currentPlanConfig = $this->getToolsConfig();

        // Merge configs: base for rendering compatibility, current plan for tool behavior
        return array_merge($baseConfig, $currentPlanConfig);
    }

    /**
     * Get allowed tools for current plan - used to filter toolbox display.
     */
    public function getAllowedTools()
    {
        return array_keys($this->getToolsConfig());
    }

    #[Computed]
    public function updatedAtString(): string
    {
        return toUserIsoString($this->document->updated_at, filamentUser());
    }

    #[Computed(true)]
    public function userPermissions(): array
    {
        return [
            'canManageMembers' => filamentUser()->can('manageMembers', $this->document),
            'canEdit' => filamentUser()->can('update', $this->document),
        ];
    }

    #[Computed]
    public function participants(): Collection
    {
        return $this->document->getParticipants();
    }

    #[Computed]
    public function participantsArray(): array
    {

        return $this->participants->asDtoArray()->toArray();
    }

    #[On('roleable-entity:members-updated.{documentId}')]
    public function reloadPartipants(): void
    {
        unset($this->participants, $this->participantsArray);

    }

    public function saveDocument(string $content)
    {
        // Get the underlying Document model for authorization and updates

        $this->authorize('update', $this->document);

        try {
            // Parse the JSON content
            $editorData = json_decode($content, true);

            // Use DocumentManager to update the document
            DocumentManager::updateBlocks($this->document, $editorData);

            // Update the DTO with new content
            unset($this->updatedAtString,$this->document);

        } catch (Exception $e) {
            Log::error('Document save failed', [
                'page_id' => $this->page['id'],
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
}
