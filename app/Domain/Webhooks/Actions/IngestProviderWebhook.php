<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\ComputeProviderEventFingerprint;
use App\Domain\Webhooks\Data\NormalizedProviderEvent;
use App\Jobs\ProcessProviderWebhook;
use App\Models\Instance;
use App\Models\ProviderEventFingerprint;
use App\Support\CorrelationId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class IngestProviderWebhook
{
    public function __construct(private ComputeProviderEventFingerprint $fingerprint) {}

    public function __invoke(Instance $instance, NormalizedProviderEvent $event): ProviderEventFingerprint
    {
        $hash = ($this->fingerprint)($instance->id, $event);
        $retentionDays = (int) config('zap.webhooks.fingerprint_retention_days', 7);

        try {
            $record = DB::transaction(function () use ($instance, $event, $hash, $retentionDays): ProviderEventFingerprint {
                return ProviderEventFingerprint::query()->create([
                    'workspace_id' => $instance->workspace_id,
                    'instance_id' => $instance->id,
                    'fingerprint' => $hash,
                    'provider_event_type' => $event->providerEventType,
                    'provider_event_id' => $event->providerEventId,
                    'event_type' => $event->eventType,
                    'payload' => $event->payload,
                    'received_at' => now(),
                    'expires_at' => now()->addDays($retentionDays),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            $existing = ProviderEventFingerprint::query()
                ->where('instance_id', $instance->id)
                ->where('fingerprint', $hash)
                ->firstOrFail();

            if ($existing->processed_at === null) {
                ProcessProviderWebhook::dispatch($existing->id, $instance->id)
                    ->onQueue($event->eventType->queue());
            }

            return $existing;
        }

        ProcessProviderWebhook::dispatch($record->id, $instance->id)
            ->onQueue($event->eventType->queue());

        Log::info('Provider webhook accepted', [
            'instance_id' => $instance->id,
            'event_type' => $event->eventType->value,
            'provider_event_type' => $event->providerEventType,
            'request_id' => CorrelationId::get(),
        ]);

        return $record;
    }
}
