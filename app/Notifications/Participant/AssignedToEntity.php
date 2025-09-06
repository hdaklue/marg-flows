<?php

declare(strict_types=1);

namespace App\Notifications\Participant;

use Hdaklue\MargRbac\Notifications\Participant\AssignedToEntity as PackageAssignedToEntity;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action;
use App\Models\Flow;
use App\Models\Tenant;
use App\Filament\Resources\Flows\FlowResource;
use App\Filament\Pages\Dashboard;
use Hdaklue\MargRbac\Contracts\HasStaticType;
use Exception;

use function get_class;

final class AssignedToEntity extends PackageAssignedToEntity
{
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
        throw_unless($this->roleable instanceof HasStaticType, new Exception('Entity must implement HasStaticType'));
        $message = "You've been added to ({$this->roleable->getTypeTitle()}) {$this->roleable->getTypeName()} as {$this->roleLabel}";

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