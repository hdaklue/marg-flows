<?php

declare(strict_types=1);

namespace App\DTOs\Page;

use App\DTOs\User\UserDto;
use Carbon\Carbon;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\DTOCast;
use WendellAdriel\ValidatedDTO\Casting\ObjectCast;
use WendellAdriel\ValidatedDTO\Concerns\Wireable;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class PageDto extends ValidatedDTO
{
    use Wireable;

    public string|int $id;

    public string $name;

    public $pageable;

    public Carbon $created_at;

    public Carbon $updated_at;

    public UserDto $creator;

    // public function toEditorJSFormat(): array
    // {
    //     return [
    //         'time' => now()->timestamp,
    //         'blocks' => $this->blocks,
    //         'version' => config('page.editorjs.version', '2.28.2'),
    //     ];
    // }

    // public function toArray(): array
    // {
    //     return [
    //         'name' => $this->name,
    //         'blocks' => $this->toEditorJSFormat(),
    //         'pageable_type' => $this->pageable->getMorphClass(),
    //         'pageable_id' => $this->pageable->getKey(),
    //         'creator_id' => $this->creator->getKey(),
    //     ];
    // }

    protected function casts(): array
    {
        return [
            'pageable' => new ObjectCast,
            'creator' => new DTOCast(UserDto::class),
            'created_at' => new CarbonCast,
            'updated_at' => new CarbonCast,
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'id' => ['required'],
            'pageable' => ['required'],
            'creator' => ['required'],
            'created_at' => ['required'],
        ];
    }

    protected function defaults(): array
    {
        return [

        ];
    }
}
