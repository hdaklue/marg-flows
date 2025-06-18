<?php

namespace App\Events\Role;

use Illuminate\Broadcasting\PrivateChannel;

class EntityRoleRemoved extends EntityRoleEvent
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
