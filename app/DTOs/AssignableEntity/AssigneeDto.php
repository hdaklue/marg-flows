<?php

declare(strict_types=1);

namespace App\DTOs\AssignableEntity;

use App\Contracts\Role\RoleableEntity;
use App\Enums\Role\RoleEnum;
use App\Facades\RoleManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class AssigneeDto extends ValidatedDTO
{
    public string $user_id;

    public ?string $role;

    public string $assignment_type;

    public ?string $entity_type;

    public ?string $entity_id;

    public static function fromUserAssignment(User $user, Model $entity, string $assignmentType = 'assignee'): self
    {
        /** @phpstan-ignore-next-line */
        $userRole = RoleManager::getRoleOn($user, $entity);

        return new self([
            'user_id' => $user->id,
            'role' => $userRole?->name,
            'assignment_type' => $assignmentType,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
        ]);
    }

    public function initials(): string
    {
        $user = User::find($this->user_id);
        $nameInitials = collect(explode(' ', $user->name ?? ''))
            ->map(fn ($name) => substr($name, 0, 1))
            ->implode('');

        return $this->role ? "{$nameInitials} ({$this->getRoleLabel()})" : $nameInitials;
    }

    public function getRoleEnum(): ?RoleEnum
    {
        return $this->role ? RoleEnum::from($this->role) : null;
    }

    public function getRoleLabel(): ?string
    {
        return $this->getRoleEnum()?->getLabel();
    }

    public function getRoleDescription(): ?string
    {
        return $this->getRoleEnum()?->getDescription();
    }

    public function getRoleLevel(): int
    {
        return $this->getRoleEnum()?->getLevel() ?? 0;
    }

    public function hasPermission(string $permission): bool
    {
        $roleEnum = $this->getRoleEnum();
        if (! $roleEnum) {
            return false;
        }

        return match ($permission) {
            'edit' => $roleEnum->isAtLeast(RoleEnum::EDITOR),
            'approve' => $roleEnum->isAtLeast(RoleEnum::MANAGER),
            'manage' => $roleEnum->isAtLeast(RoleEnum::MANAGER),
            'admin' => $roleEnum->isAtLeast(RoleEnum::ADMIN),
            'view' => $roleEnum->isAtLeast(RoleEnum::VIEWER),
            'contribute' => $roleEnum->isAtLeast(RoleEnum::CONTRIBUTOR),
            default => false,
        };
    }

    public function isAssignedAs(string $type): bool
    {
        return $this->assignment_type === $type;
    }

    public function canTransitionTo(string $newAssignmentType): bool
    {
        return match ([$this->assignment_type, $newAssignmentType]) {
            ['assignee', 'approver'] => $this->hasPermission('approve'),
            ['viewer', 'assignee'] => $this->hasPermission('edit'),
            ['assignee', 'manager'] => $this->hasPermission('manage'),
            default => false,
        };
    }

    public function getAssignmentLevel(): int
    {
        return match ($this->assignment_type) {
            'viewer' => 1,
            'assignee' => 2,
            'approver' => 3,
            'manager' => 4,
            default => 0,
        };
    }

    public function isHigherRoleThan(AssigneeDto $other): bool
    {
        return $this->getRoleLevel() > $other->getRoleLevel();
    }

    public function isHigherAssignmentThan(AssigneeDto $other): bool
    {
        return $this->getAssignmentLevel() > $other->getAssignmentLevel();
    }

    public function canAssignRole(RoleEnum $targetRole): bool
    {
        $currentRole = $this->getRoleEnum();
        if (! $currentRole) {
            return false;
        }

        return $currentRole->isHigherThan($targetRole) ||
               ($currentRole === RoleEnum::MANAGER && $targetRole->isLowerThanOrEqual(RoleEnum::EDITOR));
    }

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'string'],
            'role' => ['sometimes', 'string'],
            'assignment_type' => ['required', 'string'],
            'entity_type' => ['sometimes', 'string'],
            'entity_id' => ['sometimes', 'string'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'assignment_type' => 'assignee',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function casts(): array
    {
        return [];
    }
}
