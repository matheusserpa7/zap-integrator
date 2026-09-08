<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Instances\Actions\ApplyInstanceConnectionUpdate;
use App\Domain\Instances\Actions\ApplyInstanceQrUpdate;
use App\Domain\Messaging\Actions\PersistInboundProviderMessage;
use App\Enums\ProviderConnectionStatus;
use App\Enums\ZapEventType;
use App\Models\Instance;
use App\Models\ProviderEventFingerprint;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessProviderWebhook implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 60;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 30];

    public function __construct(public int $fingerprintId, public int $instanceId)
    {
        $this->onQueue('provider');
    }

    public function uniqueId(): string
    {
        return 'provider-webhook-'.$this->fingerprintId;
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
        return ['provider-webhook', 'instance:'.$this->instanceId];
    }

    public function handle(
        ApplyInstanceQrUpdate $applyQr,
        ApplyInstanceConnectionUpdate $applyConnection,
        PersistInboundProviderMessage $persistMessage,
    ): void {
        $fingerprint = ProviderEventFingerprint::query()
            ->with(['instance.workspace'])
            ->find($this->fingerprintId);

        if ($fingerprint === null || $fingerprint->processed_at !== null) {
            return;
        }

        $instance = $fingerprint->instance;

        if ($instance instanceof Instance) {
            $payload = $fingerprint->payload ?? [];

            match ($fingerprint->event_type) {
                ZapEventType::InstanceQrUpdated => $this->applyQr($instance, $payload, $applyQr),
                ZapEventType::InstanceConnectionUpdated => $this->applyConnection($instance, $payload, $applyConnection),
                ZapEventType::MessageReceived, ZapEventType::MessageUpdated, ZapEventType::MessageSent => $persistMessage($instance, $payload),
                default => null,
            };
        }

        $fingerprint->forceFill([
            'payload' => $this->redactPayload($fingerprint->payload ?? []),
            'processed_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Provider webhook processing failed', [
            'fingerprint_id' => $this->fingerprintId,
            'instance_id' => $this->instanceId,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyQr(Instance $instance, array $payload, ApplyInstanceQrUpdate $applyQr): void
    {
        $qr = $payload['qr_code'] ?? null;

        if (! is_string($qr) || $qr === '') {
            return;
        }

        $applyQr($instance, $qr);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyConnection(Instance $instance, array $payload, ApplyInstanceConnectionUpdate $applyConnection): void
    {
        $status = ProviderConnectionStatus::tryFrom((string) ($payload['status'] ?? ''));

        if ($status === null) {
            return;
        }

        $phone = $payload['phone_number'] ?? null;

        $applyConnection(
            $instance,
            $status,
            is_string($phone) && $phone !== '' ? $phone : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactPayload(array $payload): array
    {
        unset($payload['qr_code'], $payload['text'], $payload['caption']);

        return $payload;
    }
}
