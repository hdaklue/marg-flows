<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\TextInput;

final class PlaceholderInput extends TextInput
{
    protected string $view = 'forms.components.placeholder-input';

    protected bool|Closure $editable = false;

    public function editable(bool|Closure $editable = false): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function getEditable(): bool
    {
        return $this->evaluate($this->editable);
    }
}
