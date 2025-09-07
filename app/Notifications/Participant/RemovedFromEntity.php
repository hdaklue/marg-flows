<?php

declare(strict_types=1);

namespace App\Notifications\Participant;

use Exception;
use Filament\Notifications\Notification as FilamentNotification;
use Hdaklue\MargRbac\Contracts\HasStaticType;
use Hdaklue\MargRbac\Notifications\Participant\RemovedFromEntity as PackageRemovedFromEntity;

final class RemovedFromEntity extends PackageRemovedFromEntity
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
        throw_unless(
            $this->roleable instanceof HasStaticType,
            new Exception('Entity must implement HasStaticType'),
        );
        $message = "You've been removed from ({$this->roleable->getTypeTitle()}) {$this->roleable->getTypeName()}";

        return FilamentNotification::make()
            ->body($message)
            ->danger()
            ->getDatabaseMessage();
    }
}
