<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Facades\DocumentManager;
use App\Filament\Resources\Flows\FlowResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;

/**
 * @property-read Schema $form
 * @property-read Flow $flow
 */
final class FlowDocuments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FlowResource::class;

    #[Locked]
    public Flow $flow;

    protected string $view = 'filament.resources.flow-resource.pages.flow-documents';

    protected Width|string|null $maxContentWidth = 'full';

    public function mount(string $record)
    {
        $this->flow = Flow::where('id', $record)->first();
        $this->authorize('view', $this->flow);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('Create')
                ->form([
                    TextInput::make('title'),
                ])
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
                ->size(Size::ExtraSmall),
        ];
    }

    public function pages(): Collection
    {
        return DocumentManager::getDocumentsForUser(
            $this->flow,
            filamentUser(),
        );
    }

    public function getTitle(): string|Htmlable // @phpstan-ignore-line
    {
        return "{$this->flow->title} pages";
    }
}
