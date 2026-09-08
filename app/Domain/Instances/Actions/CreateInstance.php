<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Domain\Instances\Exceptions\InstanceQuotaExceeded;
use App\Domain\Instances\ProviderInstanceName;
use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Enums\AuditAction;
use App\Enums\InstanceProvider;
use App\Enums\InstanceStatus;
use App\Jobs\ProvisionInstance;
use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;
use App\Support\PublicId;
use Illuminate\Support\Facades\DB;

final class CreateInstance
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(Workspace $workspace, User $actor, string $name): Instance
    {
        $instance = DB::transaction(function () use ($workspace, $actor, $name): Instance {
            Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();

            $count = Instance::query()->where('workspace_id', $workspace->id)->count();

            if ($count >= $workspace->maxInstancesLimit()) {
                throw new InstanceQuotaExceeded;
            }

            $instance = new Instance([
                'workspace_id' => $workspace->id,
                'name' => $name,
                'provider' => InstanceProvider::Evolution,
                'status' => InstanceStatus::Creating,
                'provider_instance_token_encrypted' => bin2hex(random_bytes(32)),
                'provider_webhook_secret_encrypted' => bin2hex(random_bytes(32)),
            ]);
            $instance->public_id = PublicId::make('ins_');
            $instance->provider_instance_name = ProviderInstanceName::make($workspace, $instance);
            $instance->save();

            ($this->recordAuditEvent)(
                action: AuditAction::InstanceCreated,
                targetType: 'instance',
                targetId: $instance->id,
                targetPublicId: $instance->public_id,
                actor: $actor,
                workspace: $workspace,
                metadata: ['name' => $instance->name],
            );

            return $instance;
        });

        ProvisionInstance::dispatch($instance->id);

        return $instance;
    }
}
