<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class InquiryMessageCreated implements ShouldBroadcastNow
{
    public function __construct(public array $message, public int $version) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('inquiry.'.$this->message['inquiry_id'].'.v'.$this->version)];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    public function broadcastWith(): array
    {
        return $this->message;
    }
}
