<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class EditorJs extends Field
{
    public $uploadUrl = null;

    public bool|Closure $editable = true;

    protected string $view = 'forms.components.editor-js';

    public function editable(bool|Closure $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }
     public function getEditable(): bool|Closure
    {
        return $this->evaluate($this->editable);
    }
}
