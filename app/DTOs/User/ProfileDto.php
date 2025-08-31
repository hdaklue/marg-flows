<?php

declare(strict_types=1);

namespace App\DTOs\User;

use WendellAdriel\ValidatedDTO\SimpleDTO;

final class ProfileDto extends SimpleDTO
{
    public string $timezone;

    public string $avatar;

    protected function defaults(): array
    {
        return [
            'timezone' => 'UTC',
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
