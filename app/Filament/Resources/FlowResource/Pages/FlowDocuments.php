<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Facades\DocumentManager;
use App\Filament\Resources\FlowResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;

/**
 * @property-read Form $form
 * @property-read Flow $flow
 */
final class FlowDocuments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FlowResource::class;

    protected static string $view = 'filament.resources.flow-resource.pages.flow-documents';

    #[Locked]
    public Flow $flow;

    protected ?string $maxContentWidth = 'full';

    public function mount(string $record)
    {

        $this->flow = Flow::where('id', $record)->first();
        $this->authorize('view', $this->flow);

    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('Create')
                ->url(FlowResource::getUrl('createDocument', ['flow' => $this->flow->getKey()]))
                // ->form([
                //     PlaceholderInput::make('title')
                //         ->placeholder('Title'),
                //     EditorJs::make('blocks'),
                // ])
                // ->slideOver()
                // ->action(fn ($data) => dd($data))
                // ->modalWidth(MaxWidth::Full)
                ->outlined()
                ->color('primary')
                ->icon('heroicon-o-document-plus')
                ->size(ActionSize::ExtraSmall),
        ];
    }

    public function pages(): Collection
    {
        return DocumentManager::getDocumentsForUser($this->flow, filamentUser());
    }

    public function getTitle(): string|Htmlable // @phpstan-ignore-line
    {
        return "{$this->flow->title} pages";
    }
}
