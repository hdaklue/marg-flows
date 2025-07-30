<?php

declare(strict_types=1);

namespace App\DTOs;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class HashableDTO extends ValidatedDTO
{
    public string $name;

    public string $url;

    public function toTributeFormat(): array
    {
        return [
            'key' => $this->name,
            'value' => $this->name,
            'url' => $this->url,
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'url', 'max:500'],
        ];
    }

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'url' => 'string',
        ];
    }

    protected function defaults(): array
    {
        return [];
    }
}
