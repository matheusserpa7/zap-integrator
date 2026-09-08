<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Exceptions\ProviderAuthenticationFailed;
use App\Domain\Messaging\Exceptions\ProviderRateLimited;
use App\Domain\Messaging\Exceptions\ProviderUnavailable;
use App\Enums\InstanceStatus;
use App\Events\InstanceConnectionChanged;
use App\Models\Instance;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class DisconnectInstance implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 60;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 30];

    public function __construct(public int $instanceId, public int $actorUserId)
    {
        $this->onQueue('provider');
    }

    public function uniqueId(): string
    {
        return 'disconnect-'.$this->instanceId;
    }

    /**
     * @return list<WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('instance-'.$this->instanceId)];
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['provider', 'instance:'.$this->instanceId, 'actor:'.$this->actorUserId];
    }

    public function handle(MessagingProvider $provider): void
    {
        $instance = Instance::query()->with('workspace')->find($this->instanceId);

        if ($instance === null || $instance->status === InstanceStatus::Deleting) {
            return;
        }

        try {
            $provider->disconnectInstance($instance->provider_instance_name);
        } catch (ProviderAuthenticationFailed|ProviderRateLimited|ProviderUnavailable $exception) {
            throw $exception;
        }

        $instance->forceFill([
            'status' => InstanceStatus::Disconnected,
            'qr_code_encrypted' => null,
            'qr_expires_at' => null,
            'phone_number' => null,
            'connected_at' => null,
            'last_error' => null,
            'last_seen_at' => now(),
        ])->save();

        broadcast(new InstanceConnectionChanged(
            workspacePublicId: $instance->workspace->public_id,
            instancePublicId: $instance->public_id,
            status: $instance->status->value,
            phoneNumber: null,
        ));
    }
}
