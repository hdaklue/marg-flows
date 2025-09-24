<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Document\DocumentVersionPolisher;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Illuminate\View\View;
use Livewire\Component;

final class DocumentVersionItem extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $versionId;

    public string $documentId;

    public string $createdAt;

    public bool $isCurrentVersion = false;

    public null|string $creatorName = null;

    public function mount(
        string $versionId,
        string $documentId,
        string $createdAt,
        bool $isCurrentVersion = false,
        null|string $creatorName = null,
    ): void {
        $this->versionId = $versionId;
        $this->documentId = $documentId;
        $this->createdAt = $createdAt;
        $this->isCurrentVersion = $isCurrentVersion;
        $this->creatorName = $creatorName;
    }

    public function applyAction(): Action
    {
        return Action::make('apply')
            ->label('Apply')
            ->icon('heroicon-o-check')
            ->size(Size::Small)
            ->iconButton()
            ->color('primary')
            ->visible(!$this->isCurrentVersion)
            ->action(function () {
                $this->dispatch('apply-version', versionId: $this->versionId);
            });
    }

    public function openPreview(): void
    {
        $this->dispatch('openModal', component: 'document-versions-modal', arguments: [
            'documentId' => $this->documentId,
            'currentEditingVersion' => null, // We don't know the current editing version from here
            'previewVersionId' => $this->versionId,
        ]);
    }

    public function getRelativeTimeProperty(): string
    {
        return toUserDiffForHuman($this->createdAt, filamentUser());
    }

    public function getShortVersionIdProperty(): string
    {
        return DocumentVersionPolisher::shortKey($this->documentId);
    }

    public function render(): View
    {
        return view('livewire.document-version-item');
    }
}
