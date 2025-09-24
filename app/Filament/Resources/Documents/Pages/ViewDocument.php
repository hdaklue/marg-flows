<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents\Pages;

use App\Facades\DocumentManager;
use App\Filament\Resources\Documents\DocumentResource;
use App\Forms\Components\PlaceholderInput;
use App\Services\Recency\Actions\RecordRecency;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;

/**
 * @property-read bool $canEdit
 */
final class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    public null|array $data = [];

    public Width|string|null $maxContentWidth = 'full';

    protected string $view = 'filament.resources.document-resource.pages.view';

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill([
            'title' => $this->record->getAttribute('name'),
        ]);
        RecordRecency::dispatch(filamentUser(), $this->record);
    }

    public function resolveRecord(int|string $key): Model
    {
        return DocumentManager::getDocument($key);
    }

    // #[Computed]
    // public function relatedDocuments(): Collection
    // {
    //     $documentable = $this->record->documentable;

    //     return DocumentManager::getDocumentsForUser($documentable, filamentUser())->reject(
    //         fn($doc) => (
    //             $doc->getKey() === $this->record->getKey()
    //             && $doc->getMorphClass() === $this->record->getMorphClass()
    //         ),
    //     );
    // }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            PlaceholderInput::make('title')
                ->editable($this->canEdit)
                ->required()
                ->live(debounce: '100ms')
                ->minLength(10)
                ->placeholder('Page Title')
                ->columnSpanFull()
                ->maxLength(length: 100)
                ->afterStateUpdated(function ($state, $livewire) {
                    $livewire->validate();

                    if (!$this->canEdit() || blank($state)) {
                        return;
                    }

                    $this->record->update([
                        'name' => $state,
                    ]);
                }),
            // EditorJs::make('blocks')
            //     ->editable($this->canEdit)
            //     ->live()
            //     ->afterStateUpdated(fn ($state) => $this->record->update([
            //         'blocks' => $state,
            //     ])),
        ])->statePath('data');
    }

    #[Computed]
    public function canEdit(): bool
    {
        return filamentUser()->can('update', $this->record);
    }
}
