<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

final class Login extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    public function hasLogo(): bool
    {
        return true; // We'll handle logo in our custom layout
    }

    public function getTitle(): string
    {
        return __('auth.login.page_title');
    }

    public function getHeading(): string
    {
        return __('auth.login.heading');
    }

    public function getSubheading(): ?string
    {
        return __('auth.login.subheading');
    }

    // protected function getEmailFormComponent(): Component
    // {
    //     return TextInput::make('email')
    //         ->label(__('Email Address'))
    //         ->placeholder(__('Enter your email'))
    //         ->email()
    //         ->required()
    //         ->autocomplete('username')
    //         ->autofocus()
    //         ->extraInputAttributes(['tabindex' => 1]);
    // }

    // protected function getPasswordFormComponent(): Component
    // {
    //     return TextInput::make('password')
    //         ->label(__('Password'))
    //         ->placeholder(__('Enter your password'))
    //         ->password()
    //         ->revealable(filament()->arePasswordsRevealable())
    //         ->required()
    //         ->autocomplete('current-password')
    //         ->extraInputAttributes(['tabindex' => 2]);
    // }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
