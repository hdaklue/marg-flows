<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Concerns\Livewire\WithSortable;
use Exception;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

final class SortableDemo extends Component
{
    use WithSortable;

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

    protected array $sortableMessages = [
        'items.max' => 'Cannot sort more than 50 items at once',
        'items.*.required' => 'Each item must have a valid identifier',
    ];

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

    public function onSort(array $itemIds, ?array $eventData = null): array
    {
        logger()->info('onSort called', [
            'itemIds' => $itemIds,
            'eventData' => $eventData,
        ]);

        $property = $this->determinePropertyFromEventData($eventData);

        logger()->info('onSort determined property', [
            'property' => $property,
            'current_data' => $this->$property ?? null,
        ]);

        if ($property) {
            $oldData = $this->$property;
            $this->$property = $this->reorderItems($this->$property, $itemIds);

            logger()->info('onSort completed', [
                'property' => $property,
                'old_data' => $oldData,
                'new_data' => $this->$property,
            ]);

            return $this->$property;
        }

        logger()->error('onSort failed - unable to determine property');
        throw new RuntimeException('Unable to determine which property to sort');
    }

    public function onCrossGroupSort(array $itemIds, array $eventData): array
    {
        logger()->info('onCrossGroupSort called', [
            'itemIds' => $itemIds,
            'eventData' => $eventData,
        ]);

        $fromProperty = $this->getPropertyFromContainer($eventData['from']);
        $toProperty = $this->getPropertyFromContainer($eventData['to']);

        logger()->info('onCrossGroupSort properties determined', [
            'fromProperty' => $fromProperty,
            'toProperty' => $toProperty,
            'from_container' => $eventData['from'],
            'to_container' => $eventData['to'],
        ]);

        $movedItemId = $itemIds[0] ?? null;
        if (! $movedItemId) {
            logger()->error('onCrossGroupSort failed - no item ID');
            throw new InvalidArgumentException('No item ID provided for cross-group sort');
        }

        logger()->info('onCrossGroupSort moving item', [
            'itemId' => $movedItemId,
            'from' => $fromProperty,
            'to' => $toProperty,
        ]);

        $movedItem = $this->findAndRemoveItem($fromProperty, $movedItemId);

        if ($movedItem) {
            $oldStatus = $movedItem['status'];
            $movedItem['status'] = $this->getStatusFromProperty($toProperty);
            $this->addItemToProperty($toProperty, $movedItem, $eventData['newIndex'] ?? -1);

            logger()->info('onCrossGroupSort completed', [
                'itemId' => $movedItemId,
                'old_status' => $oldStatus,
                'new_status' => $movedItem['status'],
                'from_property' => $fromProperty,
                'to_property' => $toProperty,
                'from_count' => count($this->$fromProperty),
                'to_count' => count($this->$toProperty),
            ]);
        } else {
            logger()->error('onCrossGroupSort failed - item not found', [
                'itemId' => $movedItemId,
                'fromProperty' => $fromProperty,
            ]);
        }

        return $this->$toProperty;
    }

    public function addTodo(): void
    {
        $newId = (string) (count($this->todos) + count($this->inProgress) + count($this->done) + 1);

        $newTodo = [
            'id' => $newId,
            'title' => "New task #{$newId}",
            'status' => 'todo',
        ];

        $this->todos[] = $newTodo;

        logger()->info('addTodo completed', [
            'new_todo' => $newTodo,
            'todos_count' => count($this->todos),
        ]);
    }

    public function removeItem(string $itemId): void
    {
        logger()->info('removeItem called', ['itemId' => $itemId]);

        try {
            $oldTodos = count($this->todos);
            $oldInProgress = count($this->inProgress);
            $oldDone = count($this->done);

            $this->todos = collect($this->todos)->reject(fn ($item) => $item['id'] === $itemId)->values()->toArray();
            $this->inProgress = collect($this->inProgress)->reject(fn ($item) => $item['id'] === $itemId)->values()->toArray();
            $this->done = collect($this->done)->reject(fn ($item) => $item['id'] === $itemId)->values()->toArray();

            logger()->info('removeItem completed', [
                'itemId' => $itemId,
                'todos_removed' => $oldTodos - count($this->todos),
                'inProgress_removed' => $oldInProgress - count($this->inProgress),
                'done_removed' => $oldDone - count($this->done),
                'new_counts' => [
                    'todos' => count($this->todos),
                    'inProgress' => count($this->inProgress),
                    'done' => count($this->done),
                ],
            ]);
        } catch (Exception $e) {
            logger()->error('removeItem failed', [
                'itemId' => $itemId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('remove', 'Failed to remove item: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.sortable-demo');
    }

    protected function getSortableConfig(): array
    {
        return [
            'validate_items' => true,
            'allow_cross_group' => true,
            'max_items' => 50,
            'debounce_ms' => 150,
        ];
    }

    private function determinePropertyFromEventData(?array $eventData): ?string
    {
        if (! $eventData || ! isset($eventData['to'])) {
            return 'todos';
        }

        return $this->getPropertyFromContainer($eventData['to']);
    }

    private function getPropertyFromContainer(string $containerData): string
    {
        if (str_contains($containerData, 'todos')) {
            return 'todos';
        }
        if (str_contains($containerData, 'in-progress')) {
            return 'inProgress';
        }
        if (str_contains($containerData, 'done')) {
            return 'done';
        }

        return 'todos';
    }

    private function getStatusFromProperty(string $property): string
    {
        return match ($property) {
            'todos' => 'todo',
            'inProgress' => 'in_progress',
            'done' => 'done',
            default => 'todo',
        };
    }

    private function findAndRemoveItem(string $property, string $itemId): ?array
    {
        $items = collect($this->$property);
        $item = $items->firstWhere('id', $itemId);

        if ($item) {
            $this->$property = $items->reject(fn ($i) => $i['id'] === $itemId)->values()->toArray();
        }

        return $item;
    }

    private function addItemToProperty(string $property, array $item, int $position): void
    {
        $items = collect($this->$property);

        if ($position === -1 || $position >= $items->count()) {
            $items->push($item);
        } else {
            $items->splice($position, 0, [$item]);
        }

        $this->$property = $items->values()->toArray();
    }
}
