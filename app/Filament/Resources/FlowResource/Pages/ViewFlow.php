<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Concerns\Livewire\WithSortable;
use App\Filament\Resources\FlowResource;
use Exception;
use Filament\Resources\Pages\ViewRecord;
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

    protected static string $view = 'filament.resources.flow-resource.pages.view-flow';

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

    protected array $sortableRules = [
        'items' => ['required', 'array', 'max:50'],
        'items.*' => ['required', 'string'],
    ];

    #[Computed]
    public function getStages(): Collection
    {
        return $this->record->stages;
    }

    public function onSort(array $itemIds, ?string $from = null, ?string $to = null): mixed
    {
        return true;
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
}
