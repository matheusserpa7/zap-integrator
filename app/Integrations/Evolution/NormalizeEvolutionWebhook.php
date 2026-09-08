<?php

declare(strict_types=1);

namespace App\Integrations\Evolution;

use App\Domain\Webhooks\Data\NormalizedProviderEvent;
use App\Enums\MessageType;
use App\Enums\ProviderConnectionStatus;
use App\Enums\ZapEventType;
use Illuminate\Support\Arr;

final class NormalizeEvolutionWebhook
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload): NormalizedProviderEvent
    {
        $providerEventType = $this->providerEventType($payload);
        $data = $this->eventData($payload);
        $eventType = $this->mapEventType($providerEventType, $data);
        $providerEventId = $this->providerEventId($providerEventType, $data);

        return new NormalizedProviderEvent(
            providerEventType: $providerEventType,
            eventType: $eventType,
            providerEventId: $providerEventId,
            payload: $this->slimPayload($eventType, $data, $payload),
            canonicalPayload: $this->canonicalPayload($providerEventType, $payload),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function providerEventType(array $payload): string
    {
        $event = $payload['event'] ?? $payload['type'] ?? '';

        if (! is_string($event) || $event === '') {
            return 'UNKNOWN';
        }

        return strtoupper(str_replace(['.', '-'], '_', $event));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function eventData(array $payload): array
    {
        $data = $payload['data'] ?? [];

        if (! is_array($data)) {
            return [];
        }

        if ($data !== [] && array_is_list($data)) {
            $first = $data[0] ?? [];

            return is_array($first) ? $first : [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapEventType(string $providerEventType, array $data): ZapEventType
    {
        return match ($providerEventType) {
            'QRCODE_UPDATED' => ZapEventType::InstanceQrUpdated,
            'CONNECTION_UPDATE' => ZapEventType::InstanceConnectionUpdated,
            'MESSAGES_UPSERT' => $this->boolean($data, 'key.fromMe')
                ? ZapEventType::MessageUpdated
                : ZapEventType::MessageReceived,
            'MESSAGES_UPDATE' => ZapEventType::MessageUpdated,
            'SEND_MESSAGE' => ZapEventType::MessageSent,
            default => ZapEventType::Ignored,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function providerEventId(string $providerEventType, array $data): ?string
    {
        $messageId = $this->stringValue($data, 'key.id') ?? $this->stringValue($data, 'id');

        if (! is_string($messageId) || $messageId === '') {
            return null;
        }

        return match ($providerEventType) {
            'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'SEND_MESSAGE' => $messageId,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function slimPayload(ZapEventType $eventType, array $data, array $payload): array
    {
        return match ($eventType) {
            ZapEventType::InstanceQrUpdated => [
                'qr_code' => $this->extractQrImage($data, $payload),
            ],
            ZapEventType::InstanceConnectionUpdated => [
                'status' => $this->connectionStatus($data)->value,
                'phone_number' => $this->phoneNumber($data),
            ],
            ZapEventType::MessageReceived, ZapEventType::MessageUpdated, ZapEventType::MessageSent => $this->messagePayload($data),
            ZapEventType::MessageFailed, ZapEventType::Ignored, ZapEventType::InstanceConnected, ZapEventType::InstanceDisconnected => [],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function canonicalPayload(string $providerEventType, array $payload): string
    {
        $stripped = $this->stripVolatile($payload, $providerEventType === 'QRCODE_UPDATED');

        return (string) json_encode($stripped, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function stripVolatile(array $value, bool $keepQrMaterial): array
    {
        $volatile = [
            'apikey',
            'date_time',
            'destination',
            'server_url',
            'sender',
            'delay',
        ];

        if (! $keepQrMaterial) {
            $volatile = [
                ...$volatile,
                'base64',
                'message',
                'media',
                'jpegThumbnail',
                'messageTimestamp',
            ];
        }

        $clean = [];

        foreach ($value as $key => $item) {
            if (in_array((string) $key, $volatile, true)) {
                continue;
            }

            $clean[(string) $key] = is_array($item) ? $this->stripVolatile($item, $keepQrMaterial) : $item;
        }

        ksort($clean);

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    private function extractQrImage(array $data, array $payload): ?string
    {
        $candidates = [
            Arr::get($data, 'qrcode.base64'),
            Arr::get($data, 'base64'),
            Arr::get($data, 'qrcode.qrcode'),
            Arr::get($payload, 'qrcode.base64'),
            Arr::get($payload, 'base64'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if (str_starts_with($candidate, 'data:image/')) {
                return $candidate;
            }

            return 'data:image/png;base64,'.$candidate;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function connectionStatus(array $data): ProviderConnectionStatus
    {
        $state = strtolower((string) (
            Arr::get($data, 'state')
            ?? Arr::get($data, 'instance.state')
            ?? Arr::get($data, 'status')
            ?? 'close'
        ));

        return match ($state) {
            'open', 'connected' => ProviderConnectionStatus::Connected,
            'connecting' => ProviderConnectionStatus::Connecting,
            default => ProviderConnectionStatus::Disconnected,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function phoneNumber(array $data): ?string
    {
        $phone = Arr::get($data, 'wuid')
            ?? Arr::get($data, 'owner')
            ?? Arr::get($data, 'phoneNumber')
            ?? Arr::get($data, 'instance.owner')
            ?? Arr::get($data, 'instance.wuid');

        if (! is_string($phone) || $phone === '') {
            return null;
        }

        $jid = explode('@', $phone, 2)[0];

        return $jid !== '' ? $jid : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function messagePayload(array $data): array
    {
        $remoteJid = $this->stringValue($data, 'key.remoteJid') ?? $this->stringValue($data, 'remoteJid') ?? '';
        $type = $this->messageType($data);
        $text = $this->messageText($data);
        $caption = $this->messageCaption($data);
        $media = $this->mediaMetadata($data, $type);

        return array_filter([
            'provider_message_id' => $this->stringValue($data, 'key.id') ?? $this->stringValue($data, 'id'),
            'from_me' => $this->boolean($data, 'key.fromMe'),
            'from' => $remoteJid,
            'display_name' => $this->stringValue($data, 'pushName'),
            'type' => $type->value,
            'text' => $text,
            'caption' => $caption,
            'occurred_at' => $this->occurredAt($data),
            'media' => $media,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function messageType(array $data): MessageType
    {
        $raw = strtolower((string) (
            $this->stringValue($data, 'messageType')
            ?? $this->stringValue($data, 'type')
            ?? ''
        ));

        return match ($raw) {
            'conversation', 'extendedtextmessage', 'extendedtext' => MessageType::Text,
            'imagemessage', 'image' => MessageType::Image,
            'audiomessage', 'audio' => MessageType::Audio,
            'videomessage', 'video' => MessageType::Video,
            'stickermessage', 'sticker' => MessageType::Sticker,
            'documentmessage', 'documentwithcaptionmessage', 'document' => MessageType::Document,
            'locationmessage', 'livelocationmessage', 'location' => MessageType::Location,
            'contactmessage', 'contactsarraymessage', 'contacts' => MessageType::Contacts,
            default => $this->inferTypeFromMessage($data),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function inferTypeFromMessage(array $data): MessageType
    {
        $message = Arr::get($data, 'message');

        if (! is_array($message)) {
            return MessageType::Unknown;
        }

        return match (true) {
            isset($message['conversation']), isset($message['extendedTextMessage']) => MessageType::Text,
            isset($message['imageMessage']) => MessageType::Image,
            isset($message['audioMessage']) => MessageType::Audio,
            isset($message['videoMessage']) => MessageType::Video,
            isset($message['stickerMessage']) => MessageType::Sticker,
            isset($message['documentMessage']), isset($message['documentWithCaptionMessage']) => MessageType::Document,
            isset($message['locationMessage']), isset($message['liveLocationMessage']) => MessageType::Location,
            isset($message['contactMessage']), isset($message['contactsArrayMessage']) => MessageType::Contacts,
            default => MessageType::Unknown,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function messageText(array $data): ?string
    {
        return $this->stringValue($data, 'message.conversation')
            ?? $this->stringValue($data, 'message.extendedTextMessage.text')
            ?? $this->stringValue($data, 'conversation');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function messageCaption(array $data): ?string
    {
        return $this->stringValue($data, 'message.imageMessage.caption')
            ?? $this->stringValue($data, 'message.videoMessage.caption')
            ?? $this->stringValue($data, 'message.documentMessage.caption')
            ?? $this->stringValue($data, 'message.documentWithCaptionMessage.message.documentMessage.caption');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function mediaMetadata(array $data, MessageType $type): ?array
    {
        if (! $type->hasMedia()) {
            return null;
        }

        $paths = [
            MessageType::Image->value => 'message.imageMessage',
            MessageType::Audio->value => 'message.audioMessage',
            MessageType::Video->value => 'message.videoMessage',
            MessageType::Sticker->value => 'message.stickerMessage',
            MessageType::Document->value => 'message.documentMessage',
        ];

        $node = Arr::get($data, $paths[$type->value] ?? '');

        if (! is_array($node)) {
            $document = Arr::get($data, 'message.documentWithCaptionMessage.message.documentMessage');
            $node = is_array($document) ? $document : [];
        }

        $size = Arr::get($node, 'fileLength') ?? Arr::get($node, 'fileLength.value');

        $metadata = [
            'mime_type' => $this->stringValue($node, 'mimetype') ?? $this->stringValue($node, 'mimeType'),
            'filename' => $this->stringValue($node, 'fileName') ?? $this->stringValue($node, 'filename'),
            'size_bytes' => is_numeric($size) ? (int) $size : null,
        ];

        $clean = array_filter($metadata, fn (mixed $value): bool => $value !== null && $value !== '');

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function occurredAt(array $data): ?int
    {
        $raw = Arr::get($data, 'messageTimestamp') ?? Arr::get($data, 'timestamp');

        return is_numeric($raw) ? (int) $raw : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringValue(array $data, string $path): ?string
    {
        $value = Arr::get($data, $path);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function boolean(array $data, string $path): bool
    {
        return filter_var(Arr::get($data, $path), FILTER_VALIDATE_BOOL) === true;
    }
}
