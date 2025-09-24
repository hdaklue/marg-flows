<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\DocumentVersion;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use LivewireUI\Modal\ModalComponent;

final class DocumentVersionTimelineModal extends ModalComponent
{
    public string $documentId;

    public ?string $currentEditingVersion = null;

    public int $page = 1;

    public int $perPage = 10;

    public bool $hasMoreVersions = true;

    public bool $isLoading = false;

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

    public Collection $loadedVersions;

    public function mount(string $documentId, ?string $currentEditingVersion = null): void
    {
        $this->documentId = $documentId;
        $this->currentEditingVersion = $currentEditingVersion;
        $this->loadedVersions = new Collection();

        // Load initial versions
        $this->loadMoreVersions();

        // Notify parent component that version history modal was opened
        $this->dispatch('version-history-opened');
    }

    #[On('version-selected')]
    public function handleVersionSelection(string $versionId): void
    {
        $this->currentEditingVersion = $versionId;
        $this->dispatch('DocumentVersionTimelineModal::document-version-changed', versionId: $versionId);
    }

    #[On('DocumentComponent::document-saved')]
    public function handleDocumentSaved(string $newVersionId): void
    {
        $this->currentEditingVersion = $newVersionId;
        
        // Refresh versions list to show the new version
        $this->refreshVersions();
    }

    private function refreshVersions(): void
    {
        $this->page = 1;
        $this->hasMoreVersions = true;
        $this->loadedVersions = new Collection();
        $this->loadMoreVersions();
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

    public function render(): View
    {
        return view('livewire.document-version-timeline-modal');
    }
}
