<?php

declare(strict_types=1);

namespace App\DTOs\Document;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class CreateDocumentDto extends ValidatedDTO
{
    public string $name;

    public array $blocks;

    public function toEditorJSFormat(): array
    {
        return $this->blocks;
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
        return [];
    }

    protected function defaults(): array
    {
        return [
            'blocks' => config('document.editorjs.default_blocks', []),
        ];
    }
}
