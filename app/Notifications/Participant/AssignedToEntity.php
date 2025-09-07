<?php

declare(strict_types=1);

namespace App\Notifications\Participant;

use App\Contracts\SentInNotification;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use App\Models\Tenant;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;

use function get_class;

use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Contracts\RoleContract;
use Notification;

final class AssignedToEntity extends Notification
{
    public function __construct(public readonly RoleableEntity $roleable, public readonly RoleContract $role) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        throw_unless($this->roleable instanceof SentInNotification, new Exception('Entity must implement ' . SentInNotification::class));
        $message = "You've been added to ({$this->roleable->getTypeForNotification()}) {$this->roleable->getNameForNotification()} as {$this->role->getName()}";

        return FilamentNotification::make()
            ->body($message)
            ->actions([
                Action::make('visit')
                    ->button()
                    ->url(fn () => $this->generateVisitUrl()),
            ])
            ->success()
            ->getDatabaseMessage();
    }

    protected function generateVisitUrl()
    {
        $class = get_class($this->roleable);

        return match ($class) {
            Flow::class => FlowResource::getUrl('index', ['tenant' => $this->roleable->getTenant()]),
            Tenant::class => Dashboard::getUrl(['tenant' => $this->roleable]),
            default => null,
        };
    }
}
