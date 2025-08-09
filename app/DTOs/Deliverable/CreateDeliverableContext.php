<?php

declare(strict_types=1);

namespace App\DTOs\Deliverable;

// Removed Model imports - DTO should be pure data
use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * Context data for deliverable creation - handles orchestration concerns
 * Separate from pure deliverable DTO
 */
final class CreateDeliverableContext extends ValidatedDTO
{
    public string $flow_id;
    public string $creator_id;
    public ?string $stage_id;
    public array $assignments; // Participant assignments for the Action to process
    
    protected function rules(): array
    {
        return [
            'flow_id' => ['required', 'string', 'exists:flows,id'],
            'creator_id' => ['required', 'string', 'exists:users,id'],
            'stage_id' => ['sometimes', 'nullable', 'string', 'exists:stages,id'],
            'assignments' => ['sometimes', 'array'],
            'assignments.*.user_id' => ['required', 'string', 'exists:users,id'],
            'assignments.*.role' => ['required', 'string'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'assignments' => [],
        ];
    }

    /**
     * Create context from IDs
     */
    public static function for(string $flowId, string $creatorId): self
    {
        return new self([
            'flow_id' => $flowId,
            'creator_id' => $creatorId,
            'assignments' => [],
        ]);
    }

    /**
     * Get the tenant ID from the flow relationship
     */
    public function getTenantId(): string
    {
        // Action will resolve this from the flow
        return $this->flow_id; // Placeholder - Action resolves actual tenant
    }

    /**
     * Add a participant assignment
     */
    public function addAssignment(string $userId, string $role): array
    {
        $assignments = $this->assignments;
        $assignments[] = [
            'user_id' => $userId,
            'role' => $role,
        ];
        
        return $assignments;
    }

    /**
     * Check if context has assignments
     */
    public function hasAssignments(): bool
    {
        return !empty($this->assignments);
    }
}