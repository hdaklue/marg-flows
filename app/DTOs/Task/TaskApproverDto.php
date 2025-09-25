<?php

declare(strict_types=1);

namespace App\DTOs\Task;

use App\Models\User;
use Hdaklue\MargRbac\Facades\RoleManager;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class TaskApproverDto extends ValidatedDTO
{
    public string $user_id;

    public string $user_name;

    public string $user_email;

    public ?string $role;

    public string $assignment_type;

    public static function fromUser(User $user, $taskEntity = null): self
    {
        $role = null;

        if ($taskEntity) {
            $userRole = RoleManager::getRoleOn($user, $taskEntity);
            $role = $userRole?->name;
        }

        return new self([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role' => $role,
            'assignment_type' => 'approver',
        ]);
    }

    public function initials(): string
    {
        $nameInitials = collect(explode(' ', $this->user_name))
            ->map(fn ($name) => substr($name, 0, 1))
            ->implode('');

        return $this->role ? "{$nameInitials} ({$this->role})" : $nameInitials;
    }

    public function hasRole(): bool
    {
        return ! is_null($this->role);
    }

    public function canApprove(): bool
    {
        return in_array($this->role, ['MANAGER', 'ADMIN']);
    }

    public function canManage(): bool
    {
        return in_array($this->role, ['MANAGER', 'ADMIN']);
    }

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'string'],
            'user_name' => ['required', 'string'],
            'user_email' => ['required', 'email'],
            'role' => ['sometimes', 'string'],
            'assignment_type' => ['required', 'string', 'in:approver'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'assignment_type' => 'approver',
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
