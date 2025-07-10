<?php

declare(strict_types=1);

namespace App\DTOs\Stage;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

class StageDto extends ValidatedDTO
{
    public string $name;

    public string $color;

    public array $settings;

    public int $order;

    public string $slug;

    public ?string $id;

    protected function rules(): array
    {
        return [
            'name' => 'required',
            'color' => 'required',
            'order' => 'required',
            'settings' => 'array',
        ];
    }

    protected function defaults(): array
    {
        return [
            'slug' => str($this->name)->slug()->toString(),
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
