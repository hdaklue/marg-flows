<?php

namespace App\Events\Role;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class EntityRoleRemoved extends EntityRoleEvent
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
