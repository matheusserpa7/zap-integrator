<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Domain\Webhooks\Actions\FanOutWebhookEvent;
use App\Domain\Webhooks\Actions\PersistCanonicalWebhookEvent;
use App\Domain\Webhooks\BuildCanonicalInstancePayload;
use App\Enums\InstanceStatus;
use App\Enums\ZapEventType;
use App\Events\InstanceQrUpdated;
use App\Models\Instance;
use DateTimeInterface;

final class ApplyInstanceQrUpdate
{
    public function __construct(
        private PersistCanonicalWebhookEvent $persistEvent,
        private FanOutWebhookEvent $fanOut,
        private BuildCanonicalInstancePayload $canonicalPayload,
    ) {}

    public function __invoke(Instance $instance, string $qrImage, ?DateTimeInterface $expiresAt = null): void
    {
        if ($instance->status === InstanceStatus::Deleting || $instance->status === InstanceStatus::Connected) {
            return;
        }

        $ttl = (int) config('zap.instances.qr_ttl_seconds', 60);

        $instance->loadMissing('workspace');

        $instance->forceFill([
            'status' => InstanceStatus::WaitingQr,
            'qr_code_encrypted' => $qrImage,
            'qr_expires_at' => $expiresAt ?? now()->addSeconds($ttl),
            'last_error' => null,
            'last_seen_at' => now(),
        ])->save();

        broadcast(new InstanceQrUpdated(
            workspacePublicId: $instance->workspace->public_id,
            instancePublicId: $instance->public_id,
            status: $instance->status->value,
            qrCode: $instance->qrCodeForClient(),
            qrExpiresAt: $instance->qr_expires_at?->toIso8601String(),
        ));

        $canonical = ($this->canonicalPayload)(ZapEventType::InstanceQrUpdated, $instance);
        $event = ($this->persistEvent)($instance, ZapEventType::InstanceQrUpdated, $canonical);
        ($this->fanOut)($event);
    }
}
