<?php

declare(strict_types=1);

namespace App\DTOs\Document;

use App\DTOs\User\UserDto;
use Carbon\Carbon;
use HDaklue\LaravelDTOMorphCast\MorphCast;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\DTOCast;
use WendellAdriel\ValidatedDTO\Concerns\Wireable;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class DocumentDto extends ValidatedDTO
{
    use Wireable;

    public string|int $id;

    public string $name;

    public array $blocks;

    public Model $documentable;

    public UserDto $creator;

    public Carbon $created_at;

    public Carbon $updated_at;

    protected function casts(): array
    {
        return [
            'documentable' => new MorphCast(),
            'creator' => new DTOCast(UserDto::class),
            'created_at' => new CarbonCast(),
            'updated_at' => new CarbonCast(),
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'id' => ['required'],
            'documentable' => ['required', 'array'],
            'blocks' => ['array'],
            'creator' => ['required'],
            'created_at' => ['required'],
            'updated_at' => ['required'],
        ];
    }

    protected function defaults(): array
    {
        return [];
    }
}
