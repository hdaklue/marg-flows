<?php

namespace App\Forms\Components\Concers;

use Filament\Resources\Pages\EditRecord\Concerns\Translatable;
use Illuminate\Support\Arr;

trait hasTranslatableEditorJS
{
    use Translatable;

    public function updatedActiveLocale(): void
    {

        if (blank($this->oldActiveLocale)) {
            return;
        }

        $this->resetValidation();

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only($this->data, $translatableAttributes);

        $this->data = [
            ...Arr::except($this->data, $translatableAttributes),
            ...$this->otherLocaleData[$this->activeLocale] ?? [],
        ];

        unset($this->otherLocaleData[$this->activeLocale]);

        $this->dispatch('locale-changed', locale: $this->activeLocale);
    }
}
