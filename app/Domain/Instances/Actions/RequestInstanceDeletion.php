<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Enums\AuditAction;
use App\Enums\InstanceStatus;
use App\Jobs\DeleteInstance;
use App\Models\Instance;
use App\Models\User;

final class RequestInstanceDeletion
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(Instance $instance, User $actor): void
    {
        if ($instance->status === InstanceStatus::Deleting) {
            return;
        }

        $instance->forceFill([
            'status' => InstanceStatus::Deleting,
        ])->save();

        DeleteInstance::dispatch($instance->id, $actor->id);

        ($this->recordAuditEvent)(
            action: AuditAction::InstanceDeleted,
            targetType: 'instance',
            targetId: $instance->id,
            targetPublicId: $instance->public_id,
            actor: $actor,
            workspace: $instance->workspace,
            metadata: ['name' => $instance->name],
        );
    }
}
