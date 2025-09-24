<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Session;
use Livewire\Component;

final class LanguageSwitch extends Component
{
    public string $currentLocale;

    public function mount(): void
    {
        $this->currentLocale = app()->getLocale();
    }

    public function changeLanguage(string $locale): void
    {
        if (!array_key_exists($locale, config('app.available_locales'))) {
            return;
        }

        Session::put('locale', $locale);
        $this->currentLocale = $locale;

        // Use JavaScript to refresh the page
        $this->dispatch('language-changed', locale: $locale);
    }

    public function render()
    {
        return view('livewire.components.language-switch', [
            'availableLocales' => config('app.available_locales'),
        ]);
    }
}
