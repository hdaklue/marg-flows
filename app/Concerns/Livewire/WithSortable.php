<?php

declare(strict_types=1);

namespace App\Concerns\Livewire;

use Exception;
use Illuminate\Validation\ValidationException;
use Log;
use RuntimeException;

trait WithSortable
{
    protected array $sortableConfig = [
        'validate_items' => true,
        'allow_cross_group' => true,
        'max_items' => 1000,
        'debounce_ms' => 100,
    ];

    /**
     * Abstract method that implementing classes must define
     * Handles sorting items within the same group.
     *
     * @param  array  $itemIds  Array of item identifiers in their new order
     * @param  string|null  $from  Source container/group identifier
     * @param  string|null  $to  Target container/group identifier
     * @return mixed The updated items or result of the sort operation
     */
    abstract public function onSort(array $itemIds, ?string $from = null, ?string $to = null): mixed;

    public function initializeWithSortable(): void
    {
        $this->mergeSortableConfig();
    }

    public function handleSort(array $itemIds, ?array $eventData = null): void
    {
        try {
            $this->validateSortableOperation($itemIds, $eventData);

            $from = $eventData['from'] ?? null;
            $to = $eventData['to'] ?? null;

            $this->beforeSort($itemIds, $from, $to);

            $result = $this->performSort($itemIds, $from, $to);

            $this->afterSort($result, $from, $to);

        } catch (ValidationException $e) {
            $this->handleSortError($e, $itemIds, $eventData);
            throw $e;
        } catch (Exception $e) {
            $this->handleSortError($e, $itemIds, $eventData);
            throw new RuntimeException('Sortable operation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function mergeSortableConfig(): void
    {
        if (method_exists($this, 'getSortableConfig')) {
            $this->sortableConfig = array_merge($this->sortableConfig, $this->getSortableConfig());
        }
    }

    protected function validateSortableOperation(array $itemIds, ?array $eventData = null): void
    {
        if (! $this->sortableConfig['validate_items']) {
            return;
        }

        throw_if(count($itemIds) > $this->sortableConfig['max_items'], ValidationException::withMessages([
            'items' => "Cannot sort more than {$this->sortableConfig['max_items']} items",
        ]));

        throw_if(empty($itemIds), ValidationException::withMessages([
            'items' => 'No items provided for sorting',
        ]));

        $uniqueItems = array_unique($itemIds);
        throw_if(count($uniqueItems) !== count($itemIds), ValidationException::withMessages([
            'items' => 'Duplicate items detected in sort operation',
        ]));

        if (property_exists($this, 'sortableRules') && ! empty($this->sortableRules)) {
            $this->validate([
                'items' => $itemIds,
                'eventData' => $eventData,
            ], array_merge([
                'items' => ['required', 'array'],
                'items.*' => ['required'],
            ], $this->sortableRules), property_exists($this, 'sortableMessages') ? $this->sortableMessages : []);
        }

        if (method_exists($this, 'validateSortableItems')) {
            $this->validateSortableItems($itemIds, $eventData);
        }
    }

    protected function performSort(array $itemIds, ?string $from, ?string $to): mixed
    {
        if ($from !== null && $to !== null && $from !== $to) {
            return $this->handleCrossGroupSort($itemIds, $from, $to);
        }

        return $this->handleSameGroupSort($itemIds, $from, $to);
    }

    protected function handleSameGroupSort(array $itemIds, ?string $from, ?string $to): mixed
    {
        return $this->onSort($itemIds, $from, $to);
    }

    protected function handleCrossGroupSort(array $itemIds, string $from, string $to): mixed
    {
        throw_unless($this->sortableConfig['allow_cross_group'], ValidationException::withMessages([
            'cross_group' => 'Cross-group sorting is not allowed',
        ]));

        if (method_exists($this, 'onCrossGroupSort')) {
            return $this->onCrossGroupSort($itemIds, $from, $to);
        }

        return $this->onSort($itemIds, $from, $to);
    }

    protected function beforeSort(array $itemIds, ?string $from, ?string $to): void
    {
        if (method_exists($this, 'beforeSortable')) {
            $this->beforeSortable($itemIds, $from, $to);
        }
    }

    protected function afterSort($result, ?string $from, ?string $to): void
    {
        if (method_exists($this, 'afterSortable')) {
            $this->afterSortable($result, $from, $to);
        }

        $this->dispatch('sortable:updated', [
            'items' => $result,
            'from' => $from,
            'to' => $to,
        ]);
    }

    protected function handleSortError(Exception $e, array $itemIds, ?array $eventData = null): void
    {
        if (method_exists($this, 'onSortError')) {
            $this->onSortError($e, $itemIds, $eventData);
        }

        $this->dispatch('sortable:error', [
            'error' => $e->getMessage(),
            'items' => $itemIds,
            'eventData' => $eventData,
        ]);

        Log::error('Sortable operation failed', [
            'component' => static::class,
            'error' => $e->getMessage(),
            'items_count' => count($itemIds),
            'event_data' => $eventData,
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
