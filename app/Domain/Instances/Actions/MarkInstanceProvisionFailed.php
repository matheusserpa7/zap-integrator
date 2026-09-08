<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Enums\InstanceStatus;
use App\Events\InstanceProvisionFailed;
use App\Models\Instance;

final class MarkInstanceProvisionFailed
{
    public function __invoke(Instance $instance): void
    {
        $instance->loadMissing('workspace');

        $instance->forceFill([
            'status' => InstanceStatus::Error,
            'last_error' => 'Não foi possível provisionar a instância. Tente novamente.',
            'qr_code_encrypted' => null,
            'qr_expires_at' => null,
            'last_seen_at' => now(),
        ])->save();

        broadcast(new InstanceProvisionFailed(
            workspacePublicId: $instance->workspace->public_id,
            instancePublicId: $instance->public_id,
            status: $instance->status->value,
            message: (string) $instance->last_error,
        ));
    }
}
