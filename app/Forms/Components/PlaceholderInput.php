<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

class PlaceholderInput extends TextInput
{
    protected string $view = 'forms.components.placeholder-input';
}
