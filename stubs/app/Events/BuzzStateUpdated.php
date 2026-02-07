<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuzzStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $state)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('buzz');
    }

    public function broadcastAs(): string
    {
        return 'buzz.state';
    }

    public function broadcastWith(): array
    {
        return $this->state;
    }
}
