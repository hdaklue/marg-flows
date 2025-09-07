<?php

declare(strict_types=1);

namespace App\DTOs\Document;

use App\Services\Document\Contracts\DocumentTemplateContract;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class CreateDocumentDto extends ValidatedDTO
{
    public string $name;

    public array $blocks;

    public static function fromTemplate(DocumentTemplateContract $documentTemplateContract): self
    {
        return self::fromArray([
            'name' => $documentTemplateContract->getName(),
            'blocks' => $documentTemplateContract->toArray(),
        ]);
    }

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
