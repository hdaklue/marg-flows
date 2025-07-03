<?php

declare(strict_types=1);

namespace App\DTOs\Invitation;

use App\Models\User;
use App\Services\Timezone;
use Illuminate\Validation\Rule;
use WendellAdriel\ValidatedDTO\Casting\ObjectCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

class InvitationDTO extends ValidatedDTO
{
    public object $sender;

    public string $email;

    public string $name;

    public array $role_data;

    public string $timezone;

    protected function rules(): array
    {

        return [
            'sender' => ['required'],
            'name' => ['required'],
            'email' => ['email', 'required', Rule::notIn(User::pluck('email'))],
            'role_data' => ['required'],
            'timezone' => ['required', Rule::in(Timezone::getTimezonesAsFlatList())],

        ];
    }

    protected function defaults(): array
    {
        return [

        ];
    }

    protected function casts(): array
    {
        return [
            'sender' => new ObjectCast,
        ];
    }
}
