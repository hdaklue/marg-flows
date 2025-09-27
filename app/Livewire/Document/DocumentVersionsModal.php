<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Document\Facades\DocumentVersionManager;
use App\Services\Document\Facades\EditorBuilder;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use LivewireUI\Modal\ModalComponent;
use Throwable;

final class DocumentVersionsModal extends ModalComponent
{
    #[Locked]
    public string $documentId;

    public ?string $selectedVersionId = null;

    public ?string $currentEditingVersion = null;

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
        ?string $currentEditingVersion = null,
        ?string $previewVersionId = null,
    ): void {
        $this->documentId = $documentId;
        $this->currentEditingVersion = $currentEditingVersion;
        $this->loadedVersions = new Collection;

        // Load initial versions
        $this->loadMoreVersions();

        // Set the initially selected version (preview if provided, otherwise actual current version)
        $this->selectedVersionId =
            $previewVersionId ?? $this->currentVersionId ?? $this->loadedVersions->first()?->id;
    }

    public function selectVersion(string $versionId): void
    {
        $this->selectedVersionId = $versionId;
    }

    public function loadMoreVersions(): void
    {
        if (! $this->hasMoreVersions || $this->isLoading) {
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
    public function selectedVersion(): ?DocumentVersion
    {
        if (! $this->selectedVersionId) {
            return null;
        }

        return $this->loadedVersions->firstWhere('id', $this->selectedVersionId);
    }

    #[Computed]
    public function document(): Document
    {
        return Document::findOrFail($this->documentId);
    }

    #[Computed]
    public function currentVersionId(): ?string
    {
        // Always get fresh data from database to avoid caching issues
        return Document::where('id', $this->documentId)->value('current_version_id');
    }

    public function isCurrentVersion(DocumentVersion $version): bool
    {
        return $version->id === $this->currentVersionId;
    }

    #[Computed]
    public function currentVersionBadgeClasses(): string
    {
        return 'bg-sky-100 text-sky-800 dark:bg-sky-900/20 dark:text-sky-300';
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
                'versions' => [
                    'current' => __('document.versions.current'),
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
        $this->sidebarCollapsed = ! $this->sidebarCollapsed;
    }

    /**
     * @throws Throwable
     */
    public function applyVersion(string $versionId): void
    {
        try {
            $version = DocumentVersion::whereKey($versionId)->firstOrFail();
            DocumentVersionManager::applyVersion($version);
            $this->currentEditingVersion = $versionId;
            $this->redirect(DocumentResource::getUrl('view', [
                'record' => $this->documentId,
                'tenant' => filamentTenant(),
            ]), true);
            Notification::make()
                ->body(__('common.messages.operation_completed'))
                ->success()
                ->send();

            $this->closeModal();
        } catch (Throwable $e) {
            logger()->error($e);
            Notification::make()
                ->body(__('common.messages.operation_failed'))
                ->danger()
                ->send();
        }
    }

    #[On('DocumentComponent::document-saved')]
    public function handleDocumentSaved(string $newVersionId): void
    {
        $this->currentEditingVersion = $newVersionId;
        $this->refreshVersions();
    }

    public function render()
    {
        return view('livewire.document.document-versions-modal');
    }

    private function refreshVersions(): void
    {
        $this->page = 1;
        $this->hasMoreVersions = true;
        $this->loadedVersions = new Collection;
        $this->loadMoreVersions();

        // Update selected version if it was the current editing one
        if ($this->selectedVersionId === $this->currentEditingVersion) {
            $this->selectedVersionId = $this->currentEditingVersion;
        }
    }
}
