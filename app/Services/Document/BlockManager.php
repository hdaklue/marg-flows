<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Services\Document\BlocksDto\HeaderBlockDto;
use App\Services\Document\BlocksDto\ParagraphBlockDto;
use Illuminate\Support\Manager;

/**
 * Block Manager for creating EditorJS blocks with fluent API.
 */
final class BlockManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'paragraph';
    }

    /**
     * Create paragraph block builder.
     */
    public function createParagraphDriver(): ParagraphBlockBuilder
    {
        return new ParagraphBlockBuilder;
    }

    /**
     * Create header block builder.
     */
    public function createHeaderDriver(): HeaderBlockBuilder
    {
        return new HeaderBlockBuilder;
    }

    /**
     * Fluent API methods.
     */
    public function paragraph(): ParagraphBlockBuilder
    {
        return $this->driver('paragraph');
    }

    public function header(): HeaderBlockBuilder
    {
        return $this->driver('header');
    }
}

/**
 * Paragraph Block Builder.
 */
final class ParagraphBlockBuilder
{
    private string $text = '';

    private array $tunes = [];

    private ?string $id = null;

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function tunes(array $tunes): self
    {
        $this->tunes = $tunes;

        return $this;
    }

    public function build(): ParagraphBlockDto
    {
        return ParagraphBlockDto::fromArray([
            'id' => $this->id ?? $this->generateId(),
            'type' => 'paragraph',
            'data' => ['text' => $this->text],
            'tunes' => $this->tunes,
        ]);
    }

    private function generateId(): string
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
    }
}

/**
 * Header Block Builder.
 */
final class HeaderBlockBuilder
{
    private string $text = '';

    private int $level = 2;

    private array $tunes = [];

    private ?string $id = null;

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function level(int $level): self
    {
        $this->level = max(1, min(6, $level));

        return $this;
    }

    public function h1(): self
    {
        return $this->level(1);
    }

    public function h2(): self
    {
        return $this->level(2);
    }

    public function h3(): self
    {
        return $this->level(3);
    }

    public function h4(): self
    {
        return $this->level(4);
    }

    public function h5(): self
    {
        return $this->level(5);
    }

    public function h6(): self
    {
        return $this->level(6);
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function tunes(array $tunes): self
    {
        $this->tunes = $tunes;

        return $this;
    }

    public function build(): HeaderBlockDto
    {
        return HeaderBlockDto::from([
            'id' => $this->id ?? $this->generateId(),
            'type' => 'header',
            'data' => [
                'text' => $this->text,
                'level' => $this->level,
            ],
            'tunes' => $this->tunes,
        ]);
    }

    private function generateId(): string
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
    }
}
