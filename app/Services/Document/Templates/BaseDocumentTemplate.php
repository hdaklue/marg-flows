<?php

declare(strict_types=1);

namespace App\Services\Document\Templates;

use App\Services\Document\Contracts\DocumentTemplateContract;
use BumpCore\EditorPhp\Block\Block;
use Exception;

abstract class BaseDocumentTemplate implements DocumentTemplateContract
{
    public static function make(): static
    {
        return new static;
    }

    public function toArray(): array
    {
        $blockCollection = collect($this->getBlocks());

        if ($blockCollection->isNotEmpty()) {
            return $blockCollection->map(fn (Block $block) => $block->toArray())->toArray();
        }
        throw new Exception('Empty template blocks in {static::class}');
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options) ?: '';
    }

    abstract public function getBlocks(): array;

    public function getConfigJson(): string
    {
        return json_encode($this->getConfigArray()) ?: '';
    }

    abstract public function getConfigArray(): array;

    abstract public function getDataArray(): array;

    public function getDataJson(): string
    {
        return json_encode($this->getDataArray()) ?: '';
    }

    abstract public static function getDescription(): string;

    abstract public static function getName(): string;
}
