<?php

declare(strict_types=1);

namespace App\Livewire;

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

    public string $createdAt;

    public bool $isCurrentVersion = false;

    public ?string $creatorName = null;

    public function mount(
        string $versionId,
        string $createdAt,
        bool $isCurrentVersion = false,
        ?string $creatorName = null,
    ): void {
        $this->versionId = $versionId;
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
            ->visible(! $this->isCurrentVersion)
            ->action(function () {
                $this->dispatch('apply-version', versionId: $this->versionId);
            });
    }

    public function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->iconButton()
            ->size(Size::Small)
            ->color('gray')
            ->visible(! $this->isCurrentVersion)
            ->action(function () {
                $this->dispatch('preview-version', versionId: $this->versionId);
            });
    }

    public function getRelativeTimeProperty(): string
    {
        return toUserDiffForHuman($this->createdAt, filamentUser());
    }

    public function getShortVersionIdProperty(): string
    {
        return substr($this->versionId, -6);
    }

    public function render(): View
    {
        return view('livewire.document-version-item');
    }
}
