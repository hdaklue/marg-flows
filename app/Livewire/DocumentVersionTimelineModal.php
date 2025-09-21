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

    public function mount(string $documentId, ?string $currentEditingVersion = null): void
    {
        $this->documentId = $documentId;
        $this->currentEditingVersion = $currentEditingVersion;

        // Notify parent component that version history modal was opened
        $this->dispatch('version-history-opened');
    }

    #[On('version-selected')]
    public function handleVersionSelection(string $versionId): void
    {
        $this->currentEditingVersion = $versionId;
        $this->dispatch('document-version-changed', versionId: $versionId);
    }

    #[Computed]
    public function versions(): Collection
    {
        return DocumentVersion::where('document_id', $this->documentId)
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.document-version-timeline-modal');
    }
}
