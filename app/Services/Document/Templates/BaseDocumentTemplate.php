<?php

declare(strict_types=1);

namespace App\Services\Document\Templates;

use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Document\Contracts\DocumentTemplateContract;
use BumpCore\EditorPhp\Block\Block;
use Exception;
use Illuminate\Support\Str;

abstract class BaseDocumentTemplate implements DocumentTemplateContract
{
    protected DocumentTemplateTranslatorInterface $translator;

    public static function make(): static
    {
        $instance = new static;
        $instance->setTranslator(app(DocumentTemplateTranslatorInterface::class));

        return $instance;
    }

    /**
     * Get the template key in snake_case format.
     * This serves as the single source of truth for template keys.
     */
    public static function getTemplateKey(): string
    {
        $className = class_basename(static::class);

        return Str::snake($className);
    }

    public function setTranslator(DocumentTemplateTranslatorInterface $translator): static
    {
        $this->translator = $translator;

        return $this;
    }

    public function toArray(): array
    {
        $blockCollection = collect($this->getBlocks());

        if ($blockCollection->isNotEmpty()) {
            return [
                'time' => $this->getTimestamp(),
                'blocks' => $blockCollection->map(
                    fn (Block $block) => $block->toArray(),
                )->toArray(),
                'version' => config('document.editorjs.version', '2.30.8'),
            ];
        }
        throw new Exception('Empty template blocks in ' . static::class);
    }

    public function getTimestamp(): int
    {
        return (int) now()->timestamp;
    }

    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);

        return $json ?: '';
    }

    abstract public function getBlocks(): array;

    public function getConfigJson(): string
    {
        $json = json_encode($this->getConfigArray(), JSON_THROW_ON_ERROR);

        return $json ?: '';
    }

    abstract public function getConfigArray(): array;

    abstract public function getDataArray(): array;

    public function getDataJson(): string
    {
        $json = json_encode($this->getDataArray(), JSON_THROW_ON_ERROR);

        return $json ?: '';
    }

    abstract public static function getDescription(): string;

    abstract public static function getName(): string;

    abstract public static function getAvailableTranslations(): array;

    /**
     * Translate a block using the template's translator.
     */
    protected function t(string $key, array $params = []): string
    {
        return $this->translator->translateBlock(static::getTemplateKey(), $key, $params);
    }
}
