<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\DTOs\BaseDto;
use WendellAdriel\ValidatedDTO\Concerns\Wireable;

final class UserDto extends BaseDto
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
            'email' => ['required', 'email'],
            'avatar' => ['sometimes', 'nullable'],
            'timezone' => ['required'],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
