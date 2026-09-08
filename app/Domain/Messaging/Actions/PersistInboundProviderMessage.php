<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Conversations\Actions\ResolveDirectConversation;
use App\Domain\Conversations\NormalizeWaId;
use App\Domain\Webhooks\Actions\FanOutWebhookEvent;
use App\Domain\Webhooks\Actions\PersistCanonicalWebhookEvent;
use App\Domain\Webhooks\BuildCanonicalMessagePayload;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Enums\ZapEventType;
use App\Events\MessageReceived as MessageReceivedBroadcast;
use App\Events\MessageSent;
use App\Jobs\IngestInboundMedia;
use App\Models\Instance;
use App\Models\Message;
use Illuminate\Support\Carbon;

final class PersistInboundProviderMessage
{
    public function __construct(
        private NormalizeWaId $normalizeWaId,
        private ResolveDirectConversation $resolveConversation,
        private PersistCanonicalWebhookEvent $persistEvent,
        private FanOutWebhookEvent $fanOut,
        private BuildCanonicalMessagePayload $canonicalPayload,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(Instance $instance, array $payload): ?Message
    {
        $from = $payload['from'] ?? null;
        $providerMessageId = $payload['provider_message_id'] ?? null;

        if (! is_string($from) || $from === '' || ($this->normalizeWaId)->isGroup($from)) {
            return null;
        }

        if (! is_string($providerMessageId) || $providerMessageId === '') {
            return null;
        }

        $existing = Message::query()
            ->where('instance_id', $instance->id)
            ->where('provider_message_id', $providerMessageId)
            ->first();

        $fromMe = (bool) ($payload['from_me'] ?? false);
        $type = MessageType::tryFrom((string) ($payload['type'] ?? '')) ?? MessageType::Unknown;
        $text = is_string($payload['text'] ?? null) ? $payload['text'] : null;
        $caption = is_string($payload['caption'] ?? null) ? $payload['caption'] : null;
        $occurredAt = $this->occurredAt($payload);
        $displayName = is_string($payload['display_name'] ?? null) ? $payload['display_name'] : null;

        if ($existing instanceof Message) {
            $this->applyStatusUpdate($existing, $fromMe);

            return $existing;
        }

        $conversation = ($this->resolveConversation)($instance, $from, $displayName);
        $direction = $fromMe ? MessageDirection::Outbound : MessageDirection::Inbound;
        $status = $fromMe ? MessageStatus::Sent : MessageStatus::Received;

        $message = Message::query()->create([
            'workspace_id' => $instance->workspace_id,
            'instance_id' => $instance->id,
            'conversation_id' => $conversation->id,
            'provider_message_id' => $providerMessageId,
            'direction' => $direction,
            'type' => $type,
            'status' => $status,
            'body' => $type->inboxBody($text),
            'occurred_at' => $occurredAt,
        ]);

        $conversation->forceFill(['last_message_at' => $occurredAt])->save();

        $eventType = $fromMe ? ZapEventType::MessageSent : ZapEventType::MessageReceived;

        if (! $type->hasMedia()) {
            $canonical = ($this->canonicalPayload)($eventType, $message->load(['conversation.contact', 'instance']), caption: $caption);
            $event = ($this->persistEvent)(
                $instance,
                $eventType,
                $canonical,
                is_string($payload['provider_event_type'] ?? null) ? $payload['provider_event_type'] : null,
                $providerMessageId,
                $occurredAt,
            );
            ($this->fanOut)($event);
        }

        if (! $fromMe) {
            broadcast(new MessageReceivedBroadcast(
                workspacePublicId: $instance->workspace->public_id,
                conversationPublicId: $conversation->public_id,
                messagePublicId: $message->public_id,
                type: $message->type->value,
                body: $message->body,
            ));
        }

        if ($type->hasMedia()) {
            $metadata = is_array($payload['media'] ?? null) ? $payload['media'] : [];
            if (is_string($caption) && $caption !== '') {
                $metadata['caption'] = $caption;
            }
            IngestInboundMedia::dispatch($message->id, $metadata);
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function occurredAt(array $payload): Carbon
    {
        $raw = $payload['occurred_at'] ?? null;

        if (is_numeric($raw)) {
            return Carbon::createFromTimestamp((int) $raw);
        }

        if (is_string($raw) && $raw !== '') {
            return Carbon::parse($raw);
        }

        return now();
    }

    private function applyStatusUpdate(Message $message, bool $fromMe): void
    {
        if (! $fromMe || $message->status === MessageStatus::Failed) {
            return;
        }

        if ($message->status === MessageStatus::Sent) {
            return;
        }

        $message->forceFill(['status' => MessageStatus::Sent])->save();
        $message->loadMissing(['instance.workspace', 'conversation']);

        broadcast(new MessageSent(
            workspacePublicId: $message->instance->workspace->public_id,
            conversationPublicId: $message->conversation->public_id,
            messagePublicId: $message->public_id,
            status: $message->status->value,
        ));
    }
}
