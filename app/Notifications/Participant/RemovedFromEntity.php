<?php

declare(strict_types=1);

namespace App\Notifications\Participant;

use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use Exception;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RemovedFromEntity extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Model|RoleableEntity $entity) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        if (! $this->entity instanceof HasStaticType) {
            throw new Exception('Entity must implement HasStaticType');
        }
        $message = "You've been removed from ({$this->entity->getTypeTitle()}) {$this->entity->getTypeName()}";

        return FilamentNotification::make()
            ->body($message)
            ->danger()
            ->getDatabaseMessage();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
