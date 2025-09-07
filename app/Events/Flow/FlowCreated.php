<?php

declare(strict_types=1);

namespace App\Events\Flow;

use App\Models\Flow;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FlowCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Flow $flow,
        public readonly User|Authenticatable $user,
    ) {}

    public function getCreator()
    {
        return $this->user;
    }

    public function getCreatedFlow(): Flow
    {
        return $this->flow;
    }

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
