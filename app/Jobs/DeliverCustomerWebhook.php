<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Webhooks\PostSignedWebhook;
use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeliverCustomerWebhook implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public int $uniqueFor = 90_000;

    public function __construct(public int $deliveryId, public int $attempt)
    {
        $this->onQueue('webhooks');
    }

    public function uniqueId(): string
    {
        return 'webhook-delivery-'.$this->deliveryId.'-'.$this->attempt;
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['webhooks', 'delivery:'.$this->deliveryId];
    }

    public function handle(PostSignedWebhook $post): void
    {
        $delivery = WebhookDelivery::query()
            ->with(['event', 'endpoint'])
            ->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $event = $delivery->event;
        $endpoint = $delivery->endpoint;

        if (! $event instanceof WebhookEvent || ! $endpoint instanceof WebhookEndpoint) {
            return;
        }

        if ($delivery->status === WebhookDeliveryStatus::Delivered) {
            return;
        }

        if ($this->attempt !== $delivery->attempt + 1) {
            return;
        }

        if ($event->expires_at->isPast() || ! $endpoint->enabled) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::DeadLetter,
                'next_retry_at' => null,
            ])->save();

            return;
        }

        $result = $post(
            $endpoint,
            $event->payload,
            $event->public_id,
            $event->type,
            isTest: false,
        );

        $delivery->forceFill([
            'attempt' => $this->attempt,
            'http_status' => $result->httpStatus,
            'response_excerpt' => $result->excerpt,
            'duration_ms' => $result->durationMs,
        ]);

        if ($result->successful) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Delivered,
                'delivered_at' => now(),
                'next_retry_at' => null,
            ])->save();

            $endpoint->forceFill(['failure_count' => 0])->save();

            return;
        }

        if (in_array($result->excerpt, ['destination_not_allowed', 'mapping_invalid', 'header_not_allowed'], true)) {
            $endpoint->forceFill(['failure_count' => $endpoint->failure_count + 1])->save();
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::DeadLetter,
                'next_retry_at' => null,
            ])->save();

            return;
        }

        $this->scheduleRetry($delivery, $endpoint);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Customer webhook job failed', [
            'delivery_id' => $this->deliveryId,
            'attempt' => $this->attempt,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }

    private function scheduleRetry(WebhookDelivery $delivery, WebhookEndpoint $endpoint): void
    {
        $maxAttempts = max(1, (int) config('zap.webhooks.max_attempts', 7));
        $delays = array_values((array) config('zap.webhooks.retry_delays_seconds', [30, 120, 600, 3600, 21_600, 86_400]));
        $jitter = max(0, (int) config('zap.webhooks.jitter_seconds', 5));

        $endpoint->forceFill(['failure_count' => $endpoint->failure_count + 1])->save();

        if ($this->attempt >= $maxAttempts) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::DeadLetter,
                'next_retry_at' => null,
            ])->save();

            return;
        }

        $baseDelay = (int) ($delays[$this->attempt - 1] ?? 86_400);
        $delay = $baseDelay + ($jitter > 0 ? random_int(0, $jitter) : 0);

        $delivery->forceFill([
            'status' => WebhookDeliveryStatus::Retrying,
            'next_retry_at' => now()->addSeconds($delay),
        ])->save();

        self::dispatch($delivery->id, $this->attempt + 1)->delay($delay);
    }
}
