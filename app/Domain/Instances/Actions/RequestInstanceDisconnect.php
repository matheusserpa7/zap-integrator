<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Domain\Instances\Exceptions\InstanceNotConnected;
use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Enums\AuditAction;
use App\Jobs\DisconnectInstance;
use App\Models\Instance;
use App\Models\User;

final class RequestInstanceDisconnect
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(Instance $instance, User $actor): void
    {
        if (! $instance->status->allowsDisconnect()) {
            throw new InstanceNotConnected;
        }

        DisconnectInstance::dispatch($instance->id, $actor->id);

        ($this->recordAuditEvent)(
            action: AuditAction::InstanceDisconnected,
            targetType: 'instance',
            targetId: $instance->id,
            targetPublicId: $instance->public_id,
            actor: $actor,
            workspace: $instance->workspace,
            metadata: ['name' => $instance->name],
        );
    }
}
