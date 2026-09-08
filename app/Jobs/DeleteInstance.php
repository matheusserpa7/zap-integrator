<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Exceptions\ProviderAuthenticationFailed;
use App\Domain\Messaging\Exceptions\ProviderRateLimited;
use App\Domain\Messaging\Exceptions\ProviderUnavailable;
use App\Models\Instance;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class DeleteInstance implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 45;

    public int $uniqueFor = 120;

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
        return 'delete-'.$this->instanceId;
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
        $instance = Instance::query()->find($this->instanceId);

        if ($instance === null) {
            return;
        }

        try {
            $provider->deleteInstance($instance->provider_instance_name);
        } catch (ProviderRateLimited|ProviderUnavailable $exception) {
            throw $exception;
        } catch (ProviderAuthenticationFailed) {
            // Local delete still proceeds so a workspace is not stuck on a provider auth failure.
        }

        $instance->delete();
    }
}
