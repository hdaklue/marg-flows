<?php

declare(strict_types=1);

namespace App\Services\Document\DTOs;

use App\Services\Document\Casts\DocumentBlocksCast;
use App\Services\Document\Collections\DocumentBlocksCollection;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class DocumentBlocksDto extends SimpleDTO
{
    public int $time;

    public string $version;

    public DocumentBlocksCollection $blocks;

    public function toEditorJSFormat(): array
    {
        return $this->blocks->toArray();
    }

    public function applyBlockFilter(array $allowedBlocks)
    {
        $newCollection = $this->blocks->filterBlocks($allowedBlocks);

        return self::fromArray([
            'time' => $this->time,
            'blocks' => $newCollection,
            'version' => $this->version,
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'blocks' => ['required', 'array'],
            'block.*' => ['required'],
        ];
    }

    protected function casts(): array
    {
        return [
            'blocks' => new DocumentBlocksCast(),
        ];
    }

    protected function defaults(): array
    {
        return [
            'blocks' => config('document.editorjs.default_blocks', []),
        ];
    }
}
