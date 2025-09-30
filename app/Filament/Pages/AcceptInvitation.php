<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\MemberInvitation;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Throwable;

final class AcceptInvitation extends Page
{
    #[Url(as: 't')]
    public string $token = '';

    protected string $view = 'filament.pages.accept-kinvitation';

    /**
     * @throws Throwable
     */
    public function mount(): bool
    {

        // load the invitation
        // make sure that invitation is not expired, and user email is the receiver email
        abort_if(! $this->canAcceptInvitation(), 404);

        return true;
    }

    /**
     * @throws Throwable
     */
    private function canAcceptInvitation(): bool
    {
        $invitation = MemberInvitation::whereKey($this->token)->first();
        if (! $invitation) {
            return false;
        }

        return filamentUser()->getAttribute('email') === $invitation->receiver_email
            && ! $invitation->expired();

    }
}
