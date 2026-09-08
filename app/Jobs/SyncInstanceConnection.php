<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Instances\Actions\ApplyInstanceConnectionUpdate;
use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Exceptions\ProviderAuthenticationFailed;
use App\Domain\Messaging\Exceptions\ProviderRateLimited;
use App\Domain\Messaging\Exceptions\ProviderUnavailable;
use App\Enums\InstanceStatus;
use App\Models\Instance;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class SyncInstanceConnection implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 20;

    public int $uniqueFor = 15;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15];

    public function __construct(public int $instanceId)
    {
        $this->onQueue('provider');
    }

    public function uniqueId(): string
    {
        return 'sync-connection-'.$this->instanceId;
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
        return ['provider', 'instance:'.$this->instanceId];
    }

    public function handle(MessagingProvider $provider, ApplyInstanceConnectionUpdate $applyConnection): void
    {
        $instance = Instance::query()->with('workspace')->find($this->instanceId);

        if ($instance === null || $instance->status->isBusy() || $instance->status === InstanceStatus::Error) {
            return;
        }

        try {
            $state = $provider->getConnectionState($instance->provider_instance_name);
        } catch (ProviderAuthenticationFailed|ProviderRateLimited|ProviderUnavailable) {
            return;
        }

        $applyConnection($instance, $state->status, $state->phoneNumber);
    }
}
