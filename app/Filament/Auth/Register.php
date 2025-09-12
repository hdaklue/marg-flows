<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Responses\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Exception;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Hdaklue\MargRbac\Rules\Username\UsernameAvailable;
use Hdaklue\MargRbac\Rules\Username\UsernameFormat;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class Register extends BaseRegister
{
    protected string $view = 'filament.auth.register';

    public function hasLogo(): bool
    {
        return true; // We'll handle logo in our custom layout
    }

    public function getTitle(): string|Htmlable
    {
        return __('auth.register.page_title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('auth.register.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('auth.register.subheading');
    }

    public function getMaxWidth(): string|Width|null
    {
        return Width::ScreenExtraLarge;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            $this->getUsernameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
        ])->statePath('data');
    }

    public function register(): ?RegistrationResponseContract
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }
        $data = $this->form->getState();

        try {
            $user = RegisterUser::run($data['email'], $data['username'], $data['password']);

            $this->sendEmailVerificationNotification($user);

            Filament::auth()->login($user);

            session()->regenerate();

            return new RegistrationResponse;
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => false,
            'maxWidth' => $this->getMaxWidth(),
        ];
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('auth.register.username'))
            ->placeholder(__('auth.register.username_placeholder'))
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(fn (
                $livewire,
                Field $component,
            ) => $livewire->validateOnly($component->getStatePath()))
            ->rules([new UsernameAvailable, new UsernameFormat])
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('auth.register.email_address'))
            ->placeholder(__('auth.register.email_placeholder'))
            ->email()
            ->required()
            ->rule('indisposable')
            ->live(onBlur: true)
            ->afterStateUpdated(fn (
                $livewire,
                Field $component,
            ) => $livewire->validateOnly($component->getStatePath()))
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('auth.register.password'))
            ->placeholder(__('auth.register.password_placeholder'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->validationAttribute(__('auth.register.password'));
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
