<?php

namespace App\Concerns\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait WithSortable
{
    protected array $sortableConfig = [
        'validate_items' => true,
        'allow_cross_group' => true,
        'max_items' => 1000,
        'debounce_ms' => 100,
    ];


    public function initializeWithSortable()
    {
        $this->mergeSortableConfig();
    }

    protected function mergeSortableConfig(): void
    {
        if (method_exists($this, 'getSortableConfig')) {
            $this->sortableConfig = array_merge($this->sortableConfig, $this->getSortableConfig());
        }
    }

    public function handleSort(array $items, ?array $eventData = null): void
    {
        try {
            $this->validateSortableOperation($items, $eventData);
            
            $this->beforeSort($items, $eventData);
            
            $result = $this->performSort($items, $eventData);
            
            $this->afterSort($result, $eventData);
            
        } catch (ValidationException $e) {
            $this->handleSortError($e, $items, $eventData);
            throw $e;
        } catch (\Exception $e) {
            $this->handleSortError($e, $items, $eventData);
            throw new \RuntimeException('Sortable operation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function validateSortableOperation(array $items, ?array $eventData = null): void
    {
        if (!$this->sortableConfig['validate_items']) {
            return;
        }

        if (count($items) > $this->sortableConfig['max_items']) {
            throw ValidationException::withMessages([
                'items' => "Cannot sort more than {$this->sortableConfig['max_items']} items"
            ]);
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'No items provided for sorting'
            ]);
        }

        $uniqueItems = array_unique($items);
        if (count($uniqueItems) !== count($items)) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate items detected in sort operation'
            ]);
        }

        if (property_exists($this, 'sortableRules') && !empty($this->sortableRules)) {
            $this->validate([
                'items' => $items,
                'eventData' => $eventData,
            ], array_merge([
                'items' => ['required', 'array'],
                'items.*' => ['required'],
            ], $this->sortableRules), property_exists($this, 'sortableMessages') ? $this->sortableMessages : []);
        }

        if (method_exists($this, 'validateSortableItems')) {
            $this->validateSortableItems($items, $eventData);
        }
    }

    protected function performSort(array $items, ?array $eventData = null): mixed
    {
        if ($eventData && isset($eventData['to'], $eventData['from'])) {
            return $this->handleCrossGroupSort($items, $eventData);
        }

        return $this->handleSingleGroupSort($items, $eventData);
    }

    protected function handleSingleGroupSort(array $items, ?array $eventData = null): mixed
    {
        if (method_exists($this, 'onSort')) {
            return $this->onSort($items, $eventData);
        }

        if (property_exists($this, 'items')) {
            $this->items = $this->reorderItems($this->items, $items);
            return $this->items;
        }

        throw new \RuntimeException('No sort handler defined. Implement onSort() method or use $items property.');
    }

    protected function handleCrossGroupSort(array $items, array $eventData): mixed
    {
        if (!$this->sortableConfig['allow_cross_group']) {
            throw ValidationException::withMessages([
                'cross_group' => 'Cross-group sorting is not allowed'
            ]);
        }

        if (method_exists($this, 'onCrossGroupSort')) {
            return $this->onCrossGroupSort($items, $eventData);
        }

        return $this->handleSingleGroupSort($items, $eventData);
    }

    protected function reorderItems($currentItems, array $sortedIds): Collection|array
    {
        if ($currentItems instanceof Collection) {
            return $currentItems->sortBy(function ($item) use ($sortedIds) {
                $itemId = $this->getItemIdentifier($item);
                $position = array_search($itemId, $sortedIds);
                return $position !== false ? $position : 9999;
            })->values();
        }

        if (is_array($currentItems)) {
            usort($currentItems, function ($a, $b) use ($sortedIds) {
                $idA = $this->getItemIdentifier($a);
                $idB = $this->getItemIdentifier($b);
                
                $posA = array_search($idA, $sortedIds);
                $posB = array_search($idB, $sortedIds);
                
                $posA = $posA !== false ? $posA : 9999;
                $posB = $posB !== false ? $posB : 9999;
                
                return $posA <=> $posB;
            });
            
            return $currentItems;
        }

        throw new \InvalidArgumentException('Items must be array or Collection');
    }

    protected function getItemIdentifier($item): string
    {
        if (is_object($item)) {
            if (isset($item->id)) return (string) $item->id;
            if (isset($item->uuid)) return (string) $item->uuid;
            if (method_exists($item, 'getKey')) return (string) $item->getKey();
            if (method_exists($item, 'getRouteKey')) return (string) $item->getRouteKey();
        }

        if (is_array($item)) {
            if (isset($item['id'])) return (string) $item['id'];
            if (isset($item['uuid'])) return (string) $item['uuid'];
        }

        if (is_string($item) || is_numeric($item)) {
            return (string) $item;
        }

        throw new \InvalidArgumentException('Cannot determine item identifier');
    }

    protected function beforeSort(array $items, ?array $eventData = null): void
    {
        if (method_exists($this, 'beforeSortable')) {
            $this->beforeSortable($items, $eventData);
        }
    }

    protected function afterSort($result, ?array $eventData = null): void
    {
        if (method_exists($this, 'afterSortable')) {
            $this->afterSortable($result, $eventData);
        }

        $this->dispatch('sortable:updated', [
            'items' => $result,
            'eventData' => $eventData,
        ]);
    }

    protected function handleSortError(\Exception $e, array $items, ?array $eventData = null): void
    {
        if (method_exists($this, 'onSortError')) {
            $this->onSortError($e, $items, $eventData);
        }

        $this->dispatch('sortable:error', [
            'error' => $e->getMessage(),
            'items' => $items,
            'eventData' => $eventData,
        ]);

        \Log::error('Sortable operation failed', [
            'component' => static::class,
            'error' => $e->getMessage(),
            'items_count' => count($items),
            'event_data' => $eventData,
            'trace' => $e->getTraceAsString(),
        ]);
    }

    public function sortableMove(string $itemId, int $newPosition, ?string $targetGroup = null): void
    {
        if (!property_exists($this, 'items')) {
            throw new \RuntimeException('No items property found for sortable move operation');
        }

        $items = collect($this->items);
        $item = $items->first(fn($item) => $this->getItemIdentifier($item) === $itemId);
        
        if (!$item) {
            throw ValidationException::withMessages([
                'item' => "Item with ID '{$itemId}' not found"
            ]);
        }

        $withoutItem = $items->reject(fn($item) => $this->getItemIdentifier($item) === $itemId);
        $reordered = $withoutItem->splice($newPosition, 0, [$item]);
        
        $this->items = $reordered->values()->all();
        
        $this->dispatch('sortable:moved', [
            'itemId' => $itemId,
            'newPosition' => $newPosition,
            'targetGroup' => $targetGroup,
        ]);
    }

    public function sortableRemove(string $itemId): void
    {
        if (!property_exists($this, 'items')) {
            throw new \RuntimeException('No items property found for sortable remove operation');
        }

        $this->items = collect($this->items)
            ->reject(fn($item) => $this->getItemIdentifier($item) === $itemId)
            ->values()
            ->all();
            
        $this->dispatch('sortable:removed', ['itemId' => $itemId]);
    }

    public function sortableAdd($item, int $position = -1): void
    {
        if (!property_exists($this, 'items')) {
            throw new \RuntimeException('No items property found for sortable add operation');
        }

        $items = collect($this->items);
        
        if ($position === -1 || $position >= $items->count()) {
            $items->push($item);
        } else {
            $items->splice($position, 0, [$item]);
        }
        
        $this->items = $items->values()->all();
        
        $this->dispatch('sortable:added', [
            'item' => $item,
            'position' => $position,
        ]);
    }

    public function getSortableItems(): array
    {
        if (property_exists($this, 'items')) {
            return is_array($this->items) ? $this->items : $this->items->toArray();
        }

        if (method_exists($this, 'getSortableData')) {
            return $this->getSortableData();
        }

        return [];
    }
}