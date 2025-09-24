<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Document\Facades\EditorBuilder;
use Exception;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use LivewireUI\Modal\ModalComponent;

final class DocumentVersionsModal extends ModalComponent
{
    #[Locked]
    public string $documentId;

    public null|string $selectedVersionId = null;

    public null|string $currentEditingVersion = null;

    public int $page = 1;

    public int $perPage = 10;

    public bool $hasMoreVersions = true;

    public bool $isLoading = false;

    public Collection $loadedVersions;

    public bool $sidebarCollapsed = false;

    public string $userPlan = 'ultimate';

    public static function closeModalOnClickAway(): bool
    {
        return false;
    }

    public static function closeModalOnEscape(): bool
    {
        return true;
    }

    public static function dispatchCloseEvent(): bool
    {
        return true;
    }

    public function mount(
        string $documentId,
        null|string $currentEditingVersion = null,
        null|string $previewVersionId = null,
    ): void {
        $this->documentId = $documentId;
        $this->currentEditingVersion = $currentEditingVersion;
        $this->loadedVersions = new Collection();

        // Load initial versions
        $this->loadMoreVersions();

        // Set initial selected version (preview if provided, otherwise current editing)
        $this->selectedVersionId =
            $previewVersionId ?? $currentEditingVersion ?? $this->loadedVersions->first()?->id;
    }

    public function selectVersion(string $versionId): void
    {
        $this->selectedVersionId = $versionId;
    }

    public function loadMoreVersions(): void
    {
        if (!$this->hasMoreVersions || $this->isLoading) {
            return;
        }

        $this->isLoading = true;

        $newVersions = DocumentVersion::where('document_id', $this->documentId)
            ->with('creator')
            ->orderByDesc('created_at')
            ->skip(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        if ($newVersions->count() < $this->perPage) {
            $this->hasMoreVersions = false;
        }

        $this->loadedVersions = $this->loadedVersions->concat($newVersions);
        $this->page++;
        $this->isLoading = false;
    }

    #[Computed]
    public function selectedVersion(): null|DocumentVersion
    {
        if (!$this->selectedVersionId) {
            return null;
        }

        return $this->loadedVersions->firstWhere('id', $this->selectedVersionId);
    }

    #[Computed]
    public function document(): Document
    {
        return Document::findOrFail($this->documentId);
    }

    public function getToolsConfig(): array
    {
        return match ($this->userPlan) {
            'simple' => EditorBuilder::simple()->build($this->documentId),
            'advanced' => EditorBuilder::advanced()->build($this->documentId),
            'ultimate' => EditorBuilder::ultimate()->build($this->documentId),
            default => throw new Exception('Unable to resolve user plan'),
        };
    }

    public function getAllowedTools(): array
    {
        return array_keys($this->getToolsConfig());
    }

    public function getFullToolsConfig(): array
    {
        $baseConfig = EditorBuilder::base()->build();
        $currentPlanConfig = $this->getToolsConfig();

        return array_merge($baseConfig, $currentPlanConfig);
    }

    public function getJavaScriptTranslations(): array
    {
        return [
            'document' => [
                'editor' => [
                    'preview_mode' => __('document.editor.preview_mode'),
                    'read_only' => __('document.editor.read_only'),
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
            ],
            'editor_ui' => [
                'ui' => __('document.ui'),
                'toolNames' => __('document.toolNames'),
                'blockTunes' => __('document.blockTunes'),
            ],
        ];
    }

    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
    }

    public function applyVersion(string $versionId): void
    {
        $this->dispatch('apply-version', versionId: $versionId);
    }

    #[On('DocumentComponent::document-saved')]
    public function handleDocumentSaved(string $newVersionId): void
    {
        $this->currentEditingVersion = $newVersionId;
        $this->refreshVersions();
    }

    public function render()
    {
        return view('livewire.document-versions-modal');
    }

    private function refreshVersions(): void
    {
        $this->page = 1;
        $this->hasMoreVersions = true;
        $this->loadedVersions = new Collection();
        $this->loadMoreVersions();

        // Update selected version if it was the current editing one
        if ($this->selectedVersionId === $this->currentEditingVersion) {
            $this->selectedVersionId = $this->currentEditingVersion;
        }
    }
}
