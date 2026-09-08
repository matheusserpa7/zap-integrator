<?php

declare(strict_types=1);

namespace App\Domain\Media\Actions;

use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Data\DownloadMediaData;
use App\Domain\Webhooks\Actions\FanOutWebhookEvent;
use App\Domain\Webhooks\Actions\PersistCanonicalWebhookEvent;
use App\Domain\Webhooks\BuildCanonicalMessagePayload;
use App\Enums\ZapEventType;
use App\Models\MediaObject;
use App\Models\Message;
use App\Support\PublicId;
use Illuminate\Support\Facades\Storage;

final class StoreInboundMedia
{
    public function __construct(
        private MessagingProvider $provider,
        private PersistCanonicalWebhookEvent $persistEvent,
        private FanOutWebhookEvent $fanOut,
        private BuildCanonicalMessagePayload $canonicalPayload,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __invoke(Message $message, array $metadata = []): ?MediaObject
    {
        if ($message->media_id !== null) {
            return $message->media;
        }

        $message->loadMissing(['instance', 'conversation.contact']);

        $downloaded = $this->provider->downloadMedia(new DownloadMediaData(
            instanceName: $message->instance->provider_instance_name,
            providerMessageId: (string) $message->provider_message_id,
        ));

        $publicId = PublicId::make('med_');
        $disk = (string) config('zap.media.disk', 'media');
        $retentionDays = max(1, (int) config('zap.media.retention_days', 7));
        $filename = $this->filename($downloaded->filename, $metadata, $publicId);

        Storage::disk($disk)->put($publicId, $downloaded->contents);

        $media = MediaObject::query()->create([
            'public_id' => $publicId,
            'workspace_id' => $message->workspace_id,
            'instance_id' => $message->instance_id,
            'message_id' => $message->id,
            'type' => $message->type,
            'mime_type' => $downloaded->mimeType !== '' ? $downloaded->mimeType : (string) ($metadata['mime_type'] ?? 'application/octet-stream'),
            'filename' => $filename,
            'size_bytes' => strlen($downloaded->contents),
            'checksum' => hash('sha256', $downloaded->contents),
            'disk_path' => $publicId,
            'expires_at' => now()->addDays($retentionDays),
        ]);

        $message->forceFill(['media_id' => $media->id])->save();

        $message->refresh()->loadMissing(['conversation.contact', 'instance', 'media']);
        $caption = is_string($metadata['caption'] ?? null) ? $metadata['caption'] : null;
        $canonical = ($this->canonicalPayload)(ZapEventType::MessageReceived, $message, $media, $caption);
        $event = ($this->persistEvent)(
            $message->instance,
            ZapEventType::MessageReceived,
            $canonical,
            null,
            $message->provider_message_id,
            $message->occurred_at,
        );
        $canonical['id'] = $event->public_id;
        $event->forceFill(['payload' => $canonical])->save();
        ($this->fanOut)($event);

        return $media;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function filename(?string $downloaded, array $metadata, string $publicId): string
    {
        foreach ([$downloaded, $metadata['filename'] ?? null] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return basename($candidate);
            }
        }

        return $publicId;
    }
}
