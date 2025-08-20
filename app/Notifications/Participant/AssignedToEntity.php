<?php

declare(strict_types=1);

namespace App\Notifications\Participant;

use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use App\Models\Tenant;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;

use function get_class;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AssignedToEntity extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Model|RoleableEntity $entity, public string $role) {}

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

        throw_unless($this->entity instanceof HasStaticType, new Exception('Entity must implement HasStaticType'));
        $message = "You've been added to ({$this->entity->getTypeTitle()}) {$this->entity->getTypeName()} as {$this->role} ";

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

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    protected function generateVisitUrl()
    {
        $class = get_class($this->entity);

        return match ($class) {
            Flow::class => FlowResource::getUrl('index', ['tenant' => $this->entity->getTenant()]),
            Tenant::class => Dashboard::getUrl(['tenant' => $this->entity]),
            default => null,
        };
    }
}
