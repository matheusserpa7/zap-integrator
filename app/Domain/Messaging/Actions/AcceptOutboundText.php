<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Conversations\Actions\ResolveDirectConversation;
use App\Domain\Instances\Exceptions\InstanceNotConnected;
use App\Enums\InstanceStatus;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Events\MessageAccepted;
use App\Jobs\SendOutboundText;
use App\Models\Instance;
use App\Models\Message;

final class AcceptOutboundText
{
    public function __construct(private ResolveDirectConversation $resolveConversation) {}

    public function __invoke(Instance $instance, string $to, string $text): Message
    {
        if ($instance->status !== InstanceStatus::Connected) {
            throw new InstanceNotConnected;
        }

        $instance->loadMissing('workspace');
        $conversation = ($this->resolveConversation)($instance, $to);
        $occurredAt = now();

        $message = Message::query()->create([
            'workspace_id' => $instance->workspace_id,
            'instance_id' => $instance->id,
            'conversation_id' => $conversation->id,
            'direction' => MessageDirection::Outbound,
            'type' => MessageType::Text,
            'status' => MessageStatus::Sending,
            'body' => $text,
            'occurred_at' => $occurredAt,
        ]);

        $conversation->forceFill(['last_message_at' => $occurredAt])->save();

        SendOutboundText::dispatch($message->id);

        broadcast(new MessageAccepted(
            workspacePublicId: $instance->workspace->public_id,
            conversationPublicId: $conversation->public_id,
            messagePublicId: $message->public_id,
            status: $message->status->value,
        ));

        return $message->load(['conversation']);
    }
}
