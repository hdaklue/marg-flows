<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\DTOs\BaseDto;
use WendellAdriel\ValidatedDTO\Casting\DTOCast;
use WendellAdriel\ValidatedDTO\Concerns\Wireable;

final class UserDto extends BaseDto
{
    use Wireable;

    public $id;

    public $name;

    public $email;

    public array $profile;

    protected function defaults(): array
    {
        return [

        ];
    }

    protected function rules(): array
    {
        return [
            'id' => ['required'],
            'name' => ['required'],
            'email' => ['required', 'email'],
        ];
    }

    protected function casts(): array
    {
        return [
            'profile' => new DTOCast(ProfileDto::class),
        ];
    }
}
