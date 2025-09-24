<?php

declare(strict_types=1);

namespace App\DTOs\Task;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class TaskDto extends ValidatedDTO
{
    public string $title;

    public string $description;

    public string $status;

    public null|int $order_column;

    public null|string $flow_id;

    public null|string $stage_id;

    public array $assigned_to;

    public array $approvers;

    public null|Carbon $start_date;

    public null|Carbon $due_date;

    public null|Carbon $completed_at;

    public int $priority;

    public bool $is_blocking;

    public array $settings;

    public null|string $id;

    public function isCompleted(): bool
    {
        return $this->status === 'Done';
    }

    public function isBlocking(): bool
    {
        return $this->is_blocking;
    }

    public function isHighPriority(): bool
    {
        return $this->priority >= 4;
    }

    public function hasAssignees(): bool
    {
        return !empty($this->assigned_to);
    }

    public function hasApprovers(): bool
    {
        return !empty($this->approvers);
    }

    public function isAssignedTo(string $userId): bool
    {
        return in_array($userId, $this->assigned_to);
    }

    public function hasApprover(string $userId): bool
    {
        return in_array($userId, $this->approvers);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string'],
            'status' => [
                'required',
                'string',
                Rule::in(['Todo', 'Doing', 'Done']),
            ],
            'order_column' => ['sometimes', 'integer'],
            'flow_id' => ['sometimes', 'string'],
            'stage_id' => ['sometimes', 'string'],
            'assigned_to' => ['sometimes', 'array'],
            'assigned_to.*' => ['string'],
            'approvers' => ['sometimes', 'array'],
            'approvers.*' => ['string'],
            'start_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'completed_at' => ['sometimes', 'date'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'is_blocking' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
            'id' => ['sometimes', 'string'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'status' => 'Todo',
            'priority' => 3,
            'is_blocking' => false,
            'settings' => [],
            'order_column' => 0,
            'assigned_to' => [],
            'approvers' => [],
        ];
    }

    protected function casts(): array
    {
        return [
            'start_date' => new CarbonCast(),
            'due_date' => new CarbonCast(),
            'completed_at' => new CarbonCast(),
        ];
    }
}
