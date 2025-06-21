<?php

declare(strict_types=1);

namespace App\DTOs\Invitation;

use App\Enums\Account\AccountType;
use App\Models\User;
use Illuminate\Validation\Rule;
use WendellAdriel\ValidatedDTO\Casting\ObjectCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

class InvitationDTO extends ValidatedDTO
{
    public string $email;

    public object $sender;

    public array $role_data;

    protected function rules(): array
    {
        $userIds = User::whereIn('acount_type', [AccountType::ADMIN->value, AccountType::MANAGER->value])
            ->pluck('id');

        return [
            'sender' => ['required'],
            'email' => ['email', 'required', Rule::notIn(User::pluck('email'))],
            'role_data' => ['required'],
        ];
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'sender' => new ObjectCast,
        ];
    }
}
