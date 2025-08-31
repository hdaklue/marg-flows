<?php

declare(strict_types=1);

namespace App\DTOs;

use WendellAdriel\ValidatedDTO\SimpleDTO;

final class LocationDto extends SimpleDTO
{
    public string $country;

    public string $country_code;

    public string $timezone;

    protected function casts(): array
    {
        return [];
    }

    protected function defaults(): array
    {
        return [];
    }
}
