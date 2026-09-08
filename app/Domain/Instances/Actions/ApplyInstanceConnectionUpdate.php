<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Domain\Instances\MapProviderConnectionState;
use App\Domain\Webhooks\Actions\FanOutWebhookEvent;
use App\Domain\Webhooks\Actions\PersistCanonicalWebhookEvent;
use App\Domain\Webhooks\BuildCanonicalInstancePayload;
use App\Enums\InstanceStatus;
use App\Enums\ProviderConnectionStatus;
use App\Enums\ZapEventType;
use App\Events\InstanceConnectionChanged;
use App\Models\Instance;

final class ApplyInstanceConnectionUpdate
{
    public function __construct(
        private MapProviderConnectionState $mapState,
        private PersistCanonicalWebhookEvent $persistEvent,
        private FanOutWebhookEvent $fanOut,
        private BuildCanonicalInstancePayload $canonicalPayload,
    ) {}

    public function __invoke(Instance $instance, ProviderConnectionStatus $providerStatus, ?string $phoneNumber = null): void
    {
        if ($instance->status === InstanceStatus::Deleting) {
            return;
        }

        $instance->loadMissing('workspace');
        $previous = $instance->status;

        $status = ($this->mapState)($instance->status, $providerStatus);

        $instance->forceFill([
            'status' => $status,
            'phone_number' => $phoneNumber ?? $instance->phone_number,
            'last_seen_at' => now(),
        ]);

        if ($status === InstanceStatus::Connected) {
            $instance->qr_code_encrypted = null;
            $instance->qr_expires_at = null;
            $instance->connected_at ??= now();
            $instance->last_error = null;
        }

        $instance->save();

        if ($status !== InstanceStatus::WaitingQr) {
            broadcast(new InstanceConnectionChanged(
                workspacePublicId: $instance->workspace->public_id,
                instancePublicId: $instance->public_id,
                status: $status->value,
                phoneNumber: $instance->phone_number,
            ));
        }

        $customerType = match (true) {
            $status === InstanceStatus::Connected && $previous !== InstanceStatus::Connected => ZapEventType::InstanceConnected,
            $status === InstanceStatus::Disconnected && $previous !== InstanceStatus::Disconnected => ZapEventType::InstanceDisconnected,
            default => null,
        };

        if ($customerType instanceof ZapEventType) {
            $canonical = ($this->canonicalPayload)($customerType, $instance);
            $event = ($this->persistEvent)($instance, $customerType, $canonical);
            ($this->fanOut)($event);
        }
    }
}
