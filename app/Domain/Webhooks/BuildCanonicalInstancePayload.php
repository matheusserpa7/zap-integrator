<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Enums\InstanceStatus;
use App\Enums\ZapEventType;
use App\Models\Instance;

final class BuildCanonicalInstancePayload
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(ZapEventType $type, Instance $instance): array
    {
        $data = [
            'instance_id' => $instance->public_id,
            'status' => $instance->status->value,
        ];

        if ($instance->phone_number !== null && $instance->phone_number !== '') {
            $data['phone_number'] = $instance->phone_number;
        }

        if ($type === ZapEventType::InstanceQrUpdated && $instance->qr_expires_at !== null) {
            $data['expires_at'] = $instance->qr_expires_at->toIso8601String();
        }

        if ($instance->status === InstanceStatus::WaitingQr) {
            $data['status'] = InstanceStatus::WaitingQr->value;
        }

        return [
            'id' => '',
            'type' => $type->value,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }
}
