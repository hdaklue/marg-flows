<?php

declare(strict_types=1);

namespace App\DTOs\User;

use WendellAdriel\ValidatedDTO\Concerns\Wireable;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class UserDto extends ValidatedDTO
{
    use Wireable;

    public $id;

    public $name;

    public $email;

    public $avatar;

    public string $timezone;

    protected function defaults(): array
    {
        return [
            'timezone' => 'UTC',
        ];
    }

    protected function rules(): array
    {
        return [
            'id' => ['required'],
            'name' => ['required'],
            'email' => ['required'],
            'avatar' => ['required'],
            'timezone' => ['required'],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
