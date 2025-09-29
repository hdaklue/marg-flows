<?php

declare(strict_types=1);

namespace App\DTOs\Invitation;

use App\Models\Tenant;
use App\Models\User;
use WendellAdriel\ValidatedDTO\Casting\ModelCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class InvitationDTO extends ValidatedDTO
{
    public User $sender;

    public Tenant $tenant;

    public string $email;

    public string $role_key;

    protected function rules(): array
    {
        return [
            'sender' => ['required'],
            'email' => ['email', 'required'],
            'role_key' => ['required'],
            'tenant' => ['required'],
        ];
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'sender' => new ModelCast(User::class),
            'tenant' => new ModelCast(Tenant::class),
        ];
    }
}
