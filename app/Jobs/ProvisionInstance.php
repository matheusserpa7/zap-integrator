<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Instances\Actions\MarkInstanceProvisionFailed;
use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Data\ConfigureProviderWebhookData;
use App\Domain\Messaging\Data\CreateProviderInstanceData;
use App\Domain\Messaging\Exceptions\ProviderAuthenticationFailed;
use App\Domain\Messaging\Exceptions\ProviderRateLimited;
use App\Domain\Messaging\Exceptions\ProviderUnavailable;
use App\Enums\InstanceStatus;
use App\Events\InstanceQrUpdated;
use App\Models\Instance;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

final class ProvisionInstance implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 45;

    public int $uniqueFor = 120;

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
        return 'provision-'.$this->instanceId;
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

    public function handle(MessagingProvider $provider, MarkInstanceProvisionFailed $markFailed): void
    {
        $instance = Instance::query()->with('workspace')->find($this->instanceId);

        if ($instance === null || $instance->status === InstanceStatus::Deleting) {
            return;
        }

        if ($instance->status === InstanceStatus::Connected) {
            return;
        }

        if ($instance->status === InstanceStatus::WaitingQr && $instance->qrCodeForClient() !== null) {
            return;
        }

        try {
            $token = $instance->provider_instance_token_encrypted ?? bin2hex(random_bytes(32));
            $created = $provider->createInstance(new CreateProviderInstanceData(
                instanceName: $instance->provider_instance_name,
                token: $token,
            ));

            $secret = $instance->provider_webhook_secret_encrypted ?? bin2hex(random_bytes(32));
            $webhookUrl = rtrim((string) config('services.evolution.webhook_base_url'), '/')
                .'/internal/webhooks/evolution/'.$instance->public_id;

            $provider->configureWebhook(new ConfigureProviderWebhookData(
                instanceName: $instance->provider_instance_name,
                url: $webhookUrl,
                secret: $secret,
            ));

            $qr = $provider->connectInstance($instance->provider_instance_name);

            $instance->forceFill([
                'provider_instance_token_encrypted' => $created->token,
                'provider_webhook_secret_encrypted' => $secret,
                'status' => InstanceStatus::WaitingQr,
                'qr_code_encrypted' => $qr->image,
                'qr_expires_at' => $qr->expiresAt,
                'last_error' => null,
                'last_seen_at' => now(),
            ])->save();

            broadcast(new InstanceQrUpdated(
                workspacePublicId: $instance->workspace->public_id,
                instancePublicId: $instance->public_id,
                status: $instance->status->value,
                qrCode: $instance->qrCodeForClient(),
                qrExpiresAt: $instance->qr_expires_at?->toIso8601String(),
            ));
        } catch (ProviderAuthenticationFailed) {
            $markFailed($instance);

            return;
        } catch (ProviderRateLimited|ProviderUnavailable $exception) {
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $instance = Instance::query()->find($this->instanceId);

        if ($instance === null || $instance->status === InstanceStatus::Deleting) {
            return;
        }

        app(MarkInstanceProvisionFailed::class)($instance);
    }
}
