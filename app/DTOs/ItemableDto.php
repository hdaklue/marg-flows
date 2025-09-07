<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;
use WendellAdriel\ValidatedDTO\Casting\CollectionCast;
use WendellAdriel\ValidatedDTO\Casting\DTOCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

abstract class ItemableDto extends BaseDto
{
    public Collection $items;

    public function defaults(): array
    {
        return [];
    }

    abstract protected function itemDTOClass(): string;

    protected function rules(): array
    {
        return [
            'items' => ['array'],
            'items.*' => ['required'],
        ];
    }

    protected function casts(): array
    {
        return [
            'items' => new CollectionCast(new DTOCast($this->itemDTOClass())),
        ];
    }
}
