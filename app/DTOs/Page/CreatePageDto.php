<?php

declare(strict_types=1);

namespace App\DTOs\Page;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class CreatePageDto extends ValidatedDTO
{
    public string $name;

    public array $blocks;

    public function toEditorJSFormat(): array
    {
        return [
            'time' => now()->timestamp,
            'blocks' => $this->blocks,
            'version' => config('page.editorjs.version', '2.28.2'),
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'blocks' => ['required', 'array'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'blocks' => config('page.editorjs.default_blocks', []),
        ];
    }
}
