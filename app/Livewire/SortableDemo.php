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
        ['id' => '9', 'title' => 'Create landing page design', 'status' => 'todo'],
        ['id' => '10', 'title' => 'Setup CI/CD pipeline', 'status' => 'todo'],
        ['id' => '11', 'title' => 'Implement user registration flow', 'status' => 'todo'],
        ['id' => '12', 'title' => 'Add email verification', 'status' => 'todo'],
        ['id' => '13', 'title' => 'Create admin dashboard', 'status' => 'todo'],
        ['id' => '14', 'title' => 'Implement search functionality', 'status' => 'todo'],
        ['id' => '15', 'title' => 'Add payment integration', 'status' => 'todo'],
        ['id' => '16', 'title' => 'Setup monitoring and logging', 'status' => 'todo'],
        ['id' => '17', 'title' => 'Write API documentation', 'status' => 'todo'],
        ['id' => '18', 'title' => 'Implement data export feature', 'status' => 'todo'],
        ['id' => '19', 'title' => 'Add multi-language support', 'status' => 'todo'],
        ['id' => '20', 'title' => 'Optimize database queries', 'status' => 'todo'],
    ];

    public $inProgress = [
        ['id' => '5', 'title' => 'Review pull requests', 'status' => 'in_progress'],
        ['id' => '6', 'title' => 'Fix reported bugs', 'status' => 'in_progress'],
        ['id' => '21', 'title' => 'Refactor authentication module', 'status' => 'in_progress'],
        ['id' => '22', 'title' => 'Update user profile UI', 'status' => 'in_progress'],
        ['id' => '23', 'title' => 'Implement real-time notifications', 'status' => 'in_progress'],
        ['id' => '24', 'title' => 'Add file upload functionality', 'status' => 'in_progress'],
        ['id' => '25', 'title' => 'Create mobile responsive design', 'status' => 'in_progress'],
        ['id' => '26', 'title' => 'Integrate third-party APIs', 'status' => 'in_progress'],
        ['id' => '27', 'title' => 'Setup automated testing', 'status' => 'in_progress'],
        ['id' => '28', 'title' => 'Optimize image loading', 'status' => 'in_progress'],
        ['id' => '29', 'title' => 'Implement caching strategy', 'status' => 'in_progress'],
        ['id' => '30', 'title' => 'Add data validation rules', 'status' => 'in_progress'],
    ];

    public $done = [
        ['id' => '7', 'title' => 'Setup project structure', 'status' => 'done'],
        ['id' => '8', 'title' => 'Create database migrations', 'status' => 'done'],
        ['id' => '31', 'title' => 'Configure development environment', 'status' => 'done'],
        ['id' => '32', 'title' => 'Setup version control', 'status' => 'done'],
        ['id' => '33', 'title' => 'Create initial wireframes', 'status' => 'done'],
        ['id' => '34', 'title' => 'Setup database schema', 'status' => 'done'],
        ['id' => '35', 'title' => 'Implement basic routing', 'status' => 'done'],
        ['id' => '36', 'title' => 'Add error handling', 'status' => 'done'],
        ['id' => '37', 'title' => 'Setup basic security measures', 'status' => 'done'],
        ['id' => '38', 'title' => 'Create user model', 'status' => 'done'],
        ['id' => '39', 'title' => 'Implement basic CRUD operations', 'status' => 'done'],
        ['id' => '40', 'title' => 'Add input validation', 'status' => 'done'],
        ['id' => '41', 'title' => 'Setup test environment', 'status' => 'done'],
        ['id' => '42', 'title' => 'Create basic layouts', 'status' => 'done'],
    ];

    protected array $sortableRules = [
        'items' => ['required', 'array', 'max:50'],
        'items.*' => ['required', 'string'],
    ];

    protected array $sortableMessages = [
        'items.max' => 'Cannot sort more than 50 items at once',
        'items.*.required' => 'Each item must have a valid identifier',
    ];

    public function getColumnsProperty(): array
    {
        return [
            [
                'id' => 'todos',
                'name' => 'Todo',
                'color' => 'zinc',
                'property' => 'todos'
            ],
            [
                'id' => 'in-progress', 
                'name' => 'In Progress',
                'color' => 'amber',
                'property' => 'inProgress'
            ],
            [
                'id' => 'done',
                'name' => 'Done', 
                'color' => 'emerald',
                'property' => 'done'
            ]
        ];
    }

    public function getAvailableColumnsFor(string $currentColumnId): array
    {
        return collect($this->columns)
            ->reject(fn($column) => $column['id'] === $currentColumnId)
            ->values()
            ->toArray();
    }

    public function moveToColumn(string $itemId, string $targetColumnId): void
    {
        // Find the item in all columns
        $sourceColumn = null;
        $item = null;
        
        foreach ($this->columns as $column) {
            $property = $column['property'];
            $foundItem = collect($this->$property)->firstWhere('id', $itemId);
            if ($foundItem) {
                $sourceColumn = $column;
                $item = $foundItem;
                break;
            }
        }
        
        if (!$item || !$sourceColumn) {
            logger()->warning('moveToColumn: Item not found', ['itemId' => $itemId]);
            return;
        }
        
        // Find target column
        $targetColumn = collect($this->columns)->firstWhere('id', $targetColumnId);
        if (!$targetColumn) {
            logger()->warning('moveToColumn: Target column not found', ['targetColumnId' => $targetColumnId]);
            return;
        }
        
        // Remove from source
        $sourceProperty = $sourceColumn['property'];
        $this->$sourceProperty = collect($this->$sourceProperty)
            ->reject(fn($i) => $i['id'] === $itemId)
            ->values()
            ->toArray();
        
        // Update status and add to target
        $item['status'] = $this->getStatusFromProperty($targetColumn['property']);
        $targetProperty = $targetColumn['property'];
        $this->$targetProperty[] = $item;
        
        logger()->info('moveToColumn completed', [
            'itemId' => $itemId,
            'from' => $sourceColumn['id'],
            'to' => $targetColumnId,
            'new_status' => $item['status']
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

    public function onSort(array $itemIds, ?string $from = null, ?string $to = null): array
    {
        logger()->info('onSort called', [
            'itemIds' => $itemIds,
            'from' => $from,
            'to' => $to,
        ]);

        // Handle cross-group sorting
        if ($from !== null && $to !== null && $from !== $to) {
            return $this->handleCrossGroupMove($itemIds, $from, $to);
        }

        // Handle same-group sorting
        $property = $this->determinePropertyFromContainer($to ?? $from ?? 'todos');

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

    public function onCrossGroupSort(array $itemIds, string $from, string $to): array
    {
        logger()->info('onCrossGroupSort called', [
            'itemIds' => $itemIds,
            'from' => $from,
            'to' => $to,
        ]);

        return $this->handleCrossGroupMove($itemIds, $from, $to);
    }

    private function handleCrossGroupMove(array $itemIds, string $from, string $to): array
    {
        $fromProperty = $this->getPropertyFromContainer($from);
        $toProperty = $this->getPropertyFromContainer($to);

        logger()->info('handleCrossGroupMove properties determined', [
            'fromProperty' => $fromProperty,
            'toProperty' => $toProperty,
            'from_container' => $from,
            'to_container' => $to,
        ]);

        $movedItemId = $itemIds[0] ?? null;
        if (!$movedItemId) {
            logger()->error('handleCrossGroupMove failed - no item ID');
            throw new InvalidArgumentException('No item ID provided for cross-group sort');
        }

        logger()->info('handleCrossGroupMove moving item', [
            'itemId' => $movedItemId,
            'from' => $fromProperty,
            'to' => $toProperty,
        ]);

        $movedItem = $this->findAndRemoveItem($fromProperty, $movedItemId);

        if ($movedItem) {
            $oldStatus = $movedItem['status'];
            $movedItem['status'] = $this->getStatusFromProperty($toProperty);
            $this->addItemToProperty($toProperty, $movedItem, -1);

            logger()->info('handleCrossGroupMove completed', [
                'itemId' => $movedItemId,
                'old_status' => $oldStatus,
                'new_status' => $movedItem['status'],
                'from_property' => $fromProperty,
                'to_property' => $toProperty,
                'from_count' => count($this->$fromProperty),
                'to_count' => count($this->$toProperty),
            ]);
        } else {
            logger()->error('handleCrossGroupMove failed - item not found', [
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

    private function determinePropertyFromContainer(string $containerData): string
    {
        return $this->getPropertyFromContainer($containerData);
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

    private function reorderItems(array $currentItems, array $sortedIds): array
    {
        usort($currentItems, function ($a, $b) use ($sortedIds) {
            $idA = $a['id'] ?? '';
            $idB = $b['id'] ?? '';
            
            $posA = array_search($idA, $sortedIds);
            $posB = array_search($idB, $sortedIds);
            
            $posA = $posA !== false ? $posA : 9999;
            $posB = $posB !== false ? $posB : 9999;
            
            return $posA <=> $posB;
        });
        
        return $currentItems;
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
