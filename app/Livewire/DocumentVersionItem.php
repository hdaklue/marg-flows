<?php

declare(strict_types=1);

namespace App\Livewire;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\ActionSize;
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

    public function mount(
        string $versionId,
        string $createdAt,
        bool $isCurrentVersion = false,
    ): void {
        $this->versionId = $versionId;
        $this->createdAt = $createdAt;
        $this->isCurrentVersion = $isCurrentVersion;
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

    public function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->iconButton()
            ->size(Size::Small)
            ->color('gray')
            ->visible(!$this->isCurrentVersion)
            ->action(function () {
                $this->dispatch('preview-version', versionId: $this->versionId);
            });
    }

    public function getRelativeTimeProperty(): string
    {
        $date = Carbon::parse($this->createdAt);
        $now = now();
        $diffInSeconds = $now->diffInSeconds($date);
        $diffInMinutes = $now->diffInMinutes($date);
        $diffInHours = $now->diffInHours($date);
        $diffInDays = $now->diffInDays($date);

        if ($diffInSeconds < 60) {
            return 'Just now';
        }

        if ($diffInMinutes < 60) {
            return $diffInMinutes . 'm ago';
        }

        if ($diffInHours < 24) {
            return $diffInHours . 'h ago';
        }

        if ($diffInDays < 7) {
            return $diffInDays . 'd ago';
        }

        return $date->format('M j, Y');
    }

    public function render(): View
    {
        return view('livewire.document-version-item');
    }
}
