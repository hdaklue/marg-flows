<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

final class RegistrationResponse implements RegistrationResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect(Filament::getUrl());
    }
}
