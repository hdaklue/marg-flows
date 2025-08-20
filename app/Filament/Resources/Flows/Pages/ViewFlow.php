<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Concerns\Livewire\WithSortable;
use App\Filament\Resources\Flows\FlowResource;
use App\Livewire\SortableDemo;
use Exception;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * @property-read Collection $stages
 */
final class ViewFlow extends ViewRecord
{
    use WithSortable;

    protected static string $resource = FlowResource::class;

    public $todos = [
        ['id' => '1', 'title' => 'Design user interface', 'status' => 'todo'],
        ['id' => '2', 'title' => 'Implement authentication', 'status' => 'todo'],
        ['id' => '3', 'title' => 'Write unit tests', 'status' => 'todo'],
        ['id' => '4', 'title' => 'Deploy to staging', 'status' => 'todo'],
    ];

    public $inProgress = [
        ['id' => '5', 'title' => 'Review pull requests', 'status' => 'in_progress'],
        ['id' => '6', 'title' => 'Fix reported bugs', 'status' => 'in_progress'],
    ];

    public $done = [
        ['id' => '7', 'title' => 'Setup project structure', 'status' => 'done'],
        ['id' => '8', 'title' => 'Create database migrations', 'status' => 'done'],
    ];

    // protected string $view = 'filament.resources.flow-resource.pages.view-flow';

    protected array $sortableRules = [
        'items' => ['required', 'array', 'max:50'],
        'items.*' => ['required', 'string'],
    ];

    public function getHeaderActions(): array
    {
        return [

        ];
    }

    #[Computed]
    public function getStages(): Collection
    {
        return $this->record->stages;
    }

    public function onSort(array $itemIds, ?string $from = null, ?string $to = null): mixed
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {

        return $schema->components([
            Livewire::make(SortableDemo::class),

        ]);
    }

    #[On('sortable:sort')]
    public function updateSort($payload)
    {

        $itemIds = $args[0] ?? [];
        $eventData = $args[1] ?? null;

        logger()->info('updateSort called', [
            'itemIds' => $payload['items'],
            'eventData' => $eventData,
            // 'args_count' => count($args),
        ]);

        try {
            $this->handleSort($itemIds, $eventData);
        } catch (Exception $e) {
            logger()->error('updateSort failed', [
                'error' => $e->getMessage(),
                'itemIds' => $itemIds,
                'eventData' => $eventData,
            ]);
            $this->addError('sort', 'Failed to update sort order: ' . $e->getMessage());
        }
    }

    public function getTitle(): string|Htmlable
    {

        return $this->record->title;
    }
}
