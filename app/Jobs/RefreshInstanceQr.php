<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Instances\Actions\ApplyInstanceQrUpdate;
use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Enums\InstanceStatus;
use App\Models\Instance;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class RefreshInstanceQr implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 60;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 30];

    public function __construct(public int $instanceId)
    {
        $this->onQueue('provider');
    }

    public function uniqueId(): string
    {
        return 'refresh-qr-'.$this->instanceId;
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

    public function handle(MessagingProvider $provider, ApplyInstanceQrUpdate $applyQr): void
    {
        $instance = Instance::query()->with('workspace')->find($this->instanceId);

        if ($instance === null || $instance->status === InstanceStatus::Connected || $instance->status->isBusy()) {
            return;
        }

        $qr = $provider->connectInstance($instance->provider_instance_name);

        $applyQr($instance, $qr->image, $qr->expiresAt);
    }
}
