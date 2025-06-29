<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class EditorJs extends Field
{
    public $uploadUrl = null;

    protected string $view = 'forms.components.editor-js';
}
