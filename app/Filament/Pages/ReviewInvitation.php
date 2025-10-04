<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Invitation\AcceptInvitation;
use App\Actions\Invitation\RejectInvitation;
use App\Models\MemberInvitation;
use Filament\Actions\Action;
use Filament\Actions\Enums\ActionStatus;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Throwable;

final class ReviewInvitation extends SimplePage
{
    protected static bool $shouldRegisterNavigation = false;

    public string $tenant_name;

    public ?MemberInvitation $invitation = null;

    protected string $token;

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
                    ->action(fn () => AcceptInvitation::run($this->invitation, filamentUser()))
                    ->after(function ($action) {
                        match ($action->getStatus()) {
                            ActionStatus::Success => redirect()->route('filament.portal.pages.dashboard', ['tenant' => filamentUser()->getActiveTenantId()]),
                            ActionStatus::Failure => Notification::make()->danger()->body(__('common.messages.operation_failed'))->send(),
                        };
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('gray')
                    ->outlined()
                    ->action(fn () => RejectInvitation::run($this->invitation, filamentUser()))
                    ->after(function ($action) {
                        match ($action->getStatus()) {
                            ActionStatus::Success => redirect()->route('filament.portal.pages.dashboard', ['tenant' => filamentUser()->getActiveTenantId()]),
                            ActionStatus::Failure => Notification::make()->danger()->body(__('common.messages.operation_failed'))->send(),
                        };
                    }),
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
        abort_if(! $this->canAcceptInvitation(), 404);

    }

    /**
     * @throws Throwable
     */
    private function canAcceptInvitation(): bool
    {

        return filamentUser()->getAttribute('email') === $this->invitation->getAttribute('receiver_email')
            && ! $this->invitation->expired() && ! $this->invitation->accepted();

    }
}
