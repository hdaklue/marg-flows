<?php

declare(strict_types=1);

namespace App\DTOs\Flow;

use App\DTOs\Stage\StageDto;
use Illuminate\Support\Collection;
use WendellAdriel\ValidatedDTO\Casting\CollectionCast;
use WendellAdriel\ValidatedDTO\Casting\DTOCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

class FlowTemplateDto extends ValidatedDTO
{
    public string $name;

    public string $slug;

    public ?string $id;

    public string $description;

    public Collection $stages;

    protected function rules(): array
    {
        return [

            'name' => 'required',
            'description' => 'required',
            'stages' => 'required',

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
        return [
            'stages' => new CollectionCast(new DTOCast(StageDto::class)),
        ];
    }
}
