<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

final class EditorJs extends Field
{
    protected string|Closure|null $uploadUrl = null;

    protected bool|Closure $editable = true;

    protected string $view = 'forms.components.editor-js';

    private ?string $cachedId = null;

    public function editable(bool|Closure $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function uploadUrl(string|Closure|null $url): static
    {
        $this->uploadUrl = $url;

        return $this;
    }

    public function getEditable(): bool
    {
        return $this->evaluate($this->editable);
    }

    public function getUploadUrl(): string
    {
        return $this->evaluate($this->uploadUrl) ?? route('uploader');
    }

    public function getHolderId(): string
    {
        return 'editor-' . $this->getName();
    }

    // Override getId to ensure consistent unique IDs
    public function getId(): string
    {
        if ($this->cachedId === null) {
            $this->cachedId = parent::getId() ?: 'editor-' . uniqid();
        }

        return $this->cachedId;
    }
}
