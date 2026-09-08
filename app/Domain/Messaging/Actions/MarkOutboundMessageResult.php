<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Webhooks\Actions\FanOutWebhookEvent;
use App\Domain\Webhooks\Actions\PersistCanonicalWebhookEvent;
use App\Domain\Webhooks\BuildCanonicalMessagePayload;
use App\Enums\MessageStatus;
use App\Enums\ZapEventType;
use App\Events\MessageFailed;
use App\Events\MessageSent;
use App\Models\Message;

final class MarkOutboundMessageResult
{
    public function __construct(
        private PersistCanonicalWebhookEvent $persistEvent,
        private FanOutWebhookEvent $fanOut,
        private BuildCanonicalMessagePayload $canonicalPayload,
    ) {}

    public function sent(Message $message, string $providerMessageId): void
    {
        $message->forceFill([
            'status' => MessageStatus::Sent,
            'provider_message_id' => $providerMessageId !== '' ? $providerMessageId : $message->provider_message_id,
        ])->save();

        $this->broadcastAndFanOut($message, ZapEventType::MessageSent);
    }

    public function failed(Message $message): void
    {
        if ($message->status === MessageStatus::Sent) {
            return;
        }

        $message->forceFill(['status' => MessageStatus::Failed])->save();
        $this->broadcastAndFanOut($message, ZapEventType::MessageFailed);
    }

    private function broadcastAndFanOut(Message $message, ZapEventType $type): void
    {
        $message->loadMissing(['instance.workspace', 'conversation.contact', 'media']);

        $canonical = ($this->canonicalPayload)($type, $message);
        $event = ($this->persistEvent)(
            $message->instance,
            $type,
            $canonical,
            null,
            $message->provider_message_id,
            $message->occurred_at,
        );
        ($this->fanOut)($event);

        $workspacePublicId = $message->instance->workspace->public_id;

        if ($type === ZapEventType::MessageSent) {
            broadcast(new MessageSent(
                workspacePublicId: $workspacePublicId,
                conversationPublicId: $message->conversation->public_id,
                messagePublicId: $message->public_id,
                status: $message->status->value,
            ));

            return;
        }

        broadcast(new MessageFailed(
            workspacePublicId: $workspacePublicId,
            conversationPublicId: $message->conversation->public_id,
            messagePublicId: $message->public_id,
            status: $message->status->value,
        ));
    }
}
