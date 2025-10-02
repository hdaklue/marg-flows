<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\MemberInvitation;
use Filament\Actions\Action;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Throwable;

final class AcceptInvitation extends SimplePage
{
    protected static bool $shouldRegisterNavigation = false;

    public string $tenant_name;

    protected string $token;

    protected MemberInvitation $invitation;

    protected string $view = 'filament.pages.accept-invitation';

    public function getHeading(): string
    {
        return 'Invitation';
    }

    public function hasTopbar(): bool
    {
        return false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make("You have been invited to join {$this->tenant_name}, you can accept or reject the invitation below."),
            Flex::make([
                Action::make('accept')
                    ->label('Accept ')
                    ->color('success')
                    ->action(fn () => dd('accepting')),
                Action::make('reject')
                    ->label('Reject')
                    ->color('gray')
                    ->outlined()
                    ->action(fn () => dd('accepting')),
            ]),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function mount(string $id): void
    {
        $this->token = $id;
        $this->invitation = MemberInvitation::whereKey($this->token)
            ->with('tenant')->firstOrFail();
        $this->tenant_name = $this->invitation?->tenant->name;
        // load the invitation
        // make sure that invitation is not expired, and user email is the receiver email
        //        abort_if(! $this->canAcceptInvitation(), 404);
        //
        //        return true;
    }

    /**
     * @throws Throwable
     */
    private function canAcceptInvitation(): bool
    {

        return filamentUser()->getAttribute('email') === $this->invitation->getAttribute('receiver_email')
            && ! $this->invitation->expired();

    }
}
