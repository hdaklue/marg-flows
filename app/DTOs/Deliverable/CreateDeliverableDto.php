<?php

declare(strict_types=1);

namespace App\DTOs\Deliverable;

use App\Enums\Deliverable\DeliverableStatus;
use App\Enums\UrgencyEnum;
use Carbon\Carbon;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\EnumCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * Pure Deliverable entity creation DTO - only deliverable-specific data
 * No flow, tenant, or creator concerns - Action handles orchestration.
 */
final class CreateDeliverableDto extends ValidatedDTO
{
    public string $title;

    public string $description;

    public string $format;

    public string $type;

    public int $status;

    public int $urgency;

    public ?Carbon $start_date;

    public ?Carbon $success_date;

    public array $settings;

    /**
     * Get attributes suitable for Deliverable model creation
     * Only includes pure deliverable data - Action adds relationships.
     */
    public function getModelAttributes(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'format' => $this->format,
            'type' => $this->type,
            'status' => $this->status,
            'urgency' => $this->priority,
            'order_column' => $this->order_column,
            'start_date' => $this->start_date,
            'success_date' => $this->success_date,
            'settings' => $this->settings,
        ];
    }

    /**
     * Get the config key for format specifications.
     */
    public function getSpecificationConfigKey(): string
    {
        return "deliverables.{$this->format->value}.{$this->type}";
    }

    /**
     * Check if deliverable has a success date set.
     */
    public function hasSuccessDate(): bool
    {
        return $this->success_date !== null;
    }

    /**
     * Check if deliverable is high priority.
     */
    public function isHighPriority(): bool
    {
        return $this->priority >= 4;
    }

    /**
     * Get a human-readable format and type combination.
     */
    public function getFormatTypeLabel(): string
    {
        return "{$this->format->getLabel()} - " . ucwords(str_replace('_', ' ', $this->type));
    }

    /**
     * Check if deliverable should start immediately.
     */
    public function shouldStartImmediately(): bool
    {
        return
            $this->start_date === null
            || $this->start_date->isPast()
            || $this->start_date->isToday();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'format' => ['required', 'string'],
            'type' => ['required', 'string'],
            'status' => ['sometimes', 'string'],
            'urgency' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'success_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'settings' => ['sometimes', 'array'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'status' => DeliverableStatus::REQUESTED->value,
            'urgency' => UrgencyEnum::NORMAL->value,
            'settings' => [],
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => new EnumCast(DeliverableStatus::class),
            'urgency' => new EnumCast(UrgencyEnum::class),
            'start_date' => new CarbonCast,
            'success_date' => new CarbonCast,
        ];
    }
}
