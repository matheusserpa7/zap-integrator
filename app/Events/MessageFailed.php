<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessageFailed implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $workspacePublicId,
        public string $conversationPublicId,
        public string $messagePublicId,
        public string $status,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('workspaces.'.$this->workspacePublicId);
    }

    public function broadcastAs(): string
    {
        return 'MessageFailed';
    }
}
