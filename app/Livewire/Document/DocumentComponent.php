<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\Collections\ParticipantsCollection;
use App\Facades\DocumentManager;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Document\Facades\EditorBuilder;
use Exception;
use Hdaklue\Porter\Facades\Porter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Log;
use Throwable;

/**
 * @property-read string  $updatedAtString
 * @property-read array $participantsArray;
 */
final class DocumentComponent extends Component
{
    //    public $canEdit = false;

    public array $content;

    // Todo: filamentTenant()->subscription()->getName()
    public string $userPlan = 'advanced'; // Default plan - testing restrictions

    #[Locked]
    public string $documentId;

//    public Document $document;

    /**
     * Current editing version for timeline integration.
     */
    public ?string $currentEditingVersion = null;

    /**
     * Indicates if new versions are available since the last check.
     */
    public bool $hasNewVersions = false;

    // private string $current_content_hash;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function mount(string $documentId): void
    {

        $this->documentId = $documentId;
        // Initialize resolver using facade - blocks already contain a full EditorJS format
        $this->currentEditingVersion = DocumentManager::getDocument($this->documentId)
            ->loadMissing('latestVersion')
            ->latestVersion->getKey();
        //        $this->document = DocumentManager::getDocument($this->documentId);
        $this->content = $this->document->blocks;

        //        if (session()->has('Document::archive-update') && session('Document::archive-update') === $documentId) {
        //            unset($this->document);
        //        }

        // $this->current_content_hash = md5(serialize($this->content));

    }

    /**
     * @throws Throwable
     */
    #[Computed]
    public function canEditComputed(): bool
    {
        return filamentUser()->can('manage', $this->document) && ! $this->document->isArchived();
    }

    #[Computed]
    public function document(): Document
    {
        return DocumentManager::getDocument($this->documentId);
    }

    /**
     * @throws Exception
     */
    public function getToolsConfig()
    {
        return match ($this->userPlan) {
            'simple' => EditorBuilder::simple()->build($this->documentId),
            'advanced' => EditorBuilder::advanced()->build($this->documentId),
            'ultimate' => EditorBuilder::ultimate()->build($this->documentId),
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
     *
     * @throws Exception
     */
    public function getFullToolsConfig(): array
    {
        // Start with base configuration to render all existing blocks
        $baseConfig = EditorBuilder::base()->build();

        // Override with current plan-specific configurations
        $currentPlanConfig = $this->getToolsConfig();

        // Merge configs: base for rendering compatibility, current plan for tool behavior
        return array_merge($baseConfig, $currentPlanConfig);
    }

    /**
     * Get allowed tools for the current plan - used to filter toolbox display.
     *
     * @throws Exception
     */
    public function getAllowedTools(): array
    {
        return array_keys($this->getToolsConfig());
    }

    /**
     * @throws Throwable
     */
    #[Computed]
    public function updatedAtString(): string
    {
        return toUserIsoString($this->document->updated_at, filamentUser());
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    #[Computed(true)]
    public function userPermissions(): array
    {
        return [
            'canManageMembers' => filamentUser()->can('manageMembers', $this->document),
            'canEdit' => filamentUser()->can('update', $this->document),
        ];
    }

    #[Computed]
    public function participants(): ParticipantsCollection
    {
        return new ParticipantsCollection(Porter::getParticipantsWithRoles($this->document));

        // return $this->document->getParticipants();
    }

    #[Computed]
    public function currentEditingVersionComputed(): ?string
    {
        // If we have a current editing version, use it
        if ($this->currentEditingVersion !== null) {
            return $this->currentEditingVersion;
        }
        // Otherwise, get the latest version from the document
        $this->document->loadMissing('latestVersion');

        return $this->document->latestVersion?->id;
    }

    #[Computed]
    public function participantsArrayComputed(): ParticipantsCollection
    {
        return $this->participants->asDtoCollection();
    }

    #[On('roleable-entity:members-updated.{documentId}')]
    public function reloadParticipants(): void
    {
        unset($this->participants, $this->participantsArray, $this->canEditComputed, $this->userPermissions);
    }

    #[On('apply-version')]
    public function handleVersionChange(string $versionId): void
    {
        $this->currentEditingVersion = $versionId;

        // Load version content for editing
        $version = DocumentVersion::find($versionId);
        if ($version) {
            $this->content = $version->content;
            $this->dispatch('version-content-loaded', content: $version->content);
        }

        // Clear computed properties that may be affected by version change
        unset($this->canEditComputed, $this->userPermissions);
    }

    /**
     * @throws AuthorizationException|Exception
     */
    public function saveDocument(string $content): void
    {
        $this->authorize('update', $this->document);

        try {
            // Parse the JSON content
            $editorData = json_decode($content, true);

            if ($editorData === null) {
                throw new Exception('Invalid JSON content provided');
            }

            // Save version to timeline
            $newVersion = DocumentVersion::create([
                'document_id' => $this->documentId,
                'content' => $editorData,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            // Update the current editing version
            $this->currentEditingVersion = $newVersion->id;

            // Use DocumentManager to update the document
            DocumentManager::updateBlocks(
                $this->document,
                $editorData,
                // $this->current_content_hash,
            );

            // Notify version modal about the new version
            $this->dispatch('DocumentComponent::document-saved', newVersionId: $newVersion->id);

            // Update the DTO with new content
            unset($this->updatedAtString, $this->document, $this->currentEditingVersionComputed, $this->canEditComputed);
        } catch (Exception $e) {
            Log::error('Document save failed', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get JavaScript translations for the document editor.
     */
    public function getJavaScriptTranslations(): array
    {
        return [
            'document' => [
                'editor' => [
                    'saving' => __('document.editor.saving'),
                    'saved' => __('document.editor.saved'),
                    'save_failed' => __('document.editor.error'),
                    'unsaved_changes' => __('document.editor.unsaved_changes'),
                    'no_changes' => __('document.editor.no_changes'),
                ],
            ],
            'editor_tools' => [
                'paragraph' => __('document.tools.paragraph'),
                'header' => __('document.tools.header'),
                'images' => __('document.tools.images'),
                'table' => __('document.tools.table'),
                'nestedList' => __('document.tools.nestedList'),
                'alert' => __('document.tools.alert'),
                'linkTool' => __('document.tools.linkTool'),
                'videoEmbed' => __('document.tools.videoEmbed'),
                'videoUpload' => __('document.tools.videoUpload'),
                'commentTune' => __('document.tools.commentTune'),
            ],
            'editor_ui' => [
                'ui' => __('document.ui'),
                'toolNames' => __('document.toolNames'),
                'blockTunes' => __('document.blockTunes'),
            ],
        ];
    }

    public function checkNewVersions(): void
    {
        // Fetch the latest version ID directly
        $latestVersion = DocumentVersion::where('document_id', $this->documentId)
            ->orderByDesc('created_at')
            ->first();
        if ($latestVersion->getKey() !== $this->currentEditingVersionComputed) {
            $this->hasNewVersions = true;
        }
    }

    /**
     * Reset new versions indicator when history modal is opened.
     */
    #[On('version-history-opened')]
    public function handleVersionHistoryOpened(): void
    {
        $this->hasNewVersions = false;
    }

    public function handleVerionsModalOpen(): void
    {
        $this->dispatch('openModal', 'document-versions-modal', [
            'documentId' => $this->documentId,
            'currentEditingVersion' => $this->currentEditingVersionComputed,
        ]);

        // $this->dispatch('openModal', { component: 'document-versions-modal', arguments: { documentId: '{{ $documentId }}', currentEditingVersion: '{{ $this->currentEditingVersionComputed }}' } });
    }

    public function render(): Factory|View
    {
        return view('livewire.page.document-component');
    }
}
