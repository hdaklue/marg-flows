<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\TextInput;

final class PlaceholderInput extends TextInput
{
    protected string $view = 'forms.components.placeholder-input';

    protected bool|Closure $editable = true;

    protected bool|Closure $showLabel = false;

    public function editable(bool|Closure $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function showLabel(bool|Closure $showLabel = false): static
    {
        $this->showLabel = $showLabel;

        return $this;
    }

    public function getShowLabel(): bool
    {
        return $this->evaluate($this->showLabel);
    }

    public function getEditable(): bool
    {
        return $this->evaluate($this->editable);
    }
}
