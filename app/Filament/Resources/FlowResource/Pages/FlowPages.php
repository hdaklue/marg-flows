<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Facades\PageManager;
use App\Filament\Resources\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * @property-read Form $form
 * @property-read Flow $flow
 */
final class FlowPages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FlowResource::class;

    protected static string $view = 'filament.resources.flow-resource.pages.flow-pages';

    #[Locked]
    public Flow $flow;

    public function mount(string $record)
    {

        $this->flow = Flow::where('id', $record)->first();
        $this->authorize('view', $this->flow);

    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('Create')
                ->outlined()
                ->color('primary')
                ->icon('heroicon-o-document-plus')
                ->size(ActionSize::ExtraSmall),
        ];
    }

    #[Computed]
    public function pages(): Collection
    {
        return PageManager::getPagesForUser($this->flow, filamentUser());
    }

    public function getTitle(): string|Htmlable // @phpstan-ignore-line
    {
        return "{$this->flow->title} pages";
    }
}
