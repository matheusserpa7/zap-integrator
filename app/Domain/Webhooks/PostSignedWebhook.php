<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Data\WebhookDeliveryAttempt;
use App\Domain\Webhooks\Exceptions\WebhookDestinationNotAllowed;
use App\Domain\Webhooks\Exceptions\WebhookHeaderNotAllowed;
use App\Domain\Webhooks\Exceptions\WebhookMappingInvalid;
use App\Enums\ZapEventType;
use App\Models\WebhookEndpoint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PostSignedWebhook
{
    public function __construct(
        private ValidateWebhookDestination $validateDestination,
        private ValidateWebhookHeaders $validateHeaders,
        private RenderWebhookPayload $render,
        private EncodeWebhookBody $encode,
        private SignWebhookRequest $sign,
    ) {}

    /**
     * @param  array<string, mixed>  $canonical
     */
    public function __invoke(
        WebhookEndpoint $endpoint,
        array $canonical,
        string $eventId,
        ZapEventType $eventType,
        bool $isTest = false,
    ): WebhookDeliveryAttempt {
        try {
            $resolved = ($this->validateDestination)($endpoint->url);
            ($this->validateHeaders)($endpoint->headerRows());

            $payload = ($this->render)->body($endpoint, $canonical);
            $rawBody = ($this->encode)($payload);
        } catch (WebhookDestinationNotAllowed) {
            return $this->failure(null, hrtime(true), 'destination_not_allowed');
        } catch (WebhookMappingInvalid) {
            return $this->failure(null, hrtime(true), 'mapping_invalid');
        } catch (WebhookHeaderNotAllowed) {
            return $this->failure(null, hrtime(true), 'header_not_allowed');
        }
        $timestamp = $this->sign->timestamp();
        $signature = $this->sign->signature($endpoint->secret_encrypted, $timestamp, $rawBody);

        $headers = ($this->render)->headers($endpoint, $canonical);
        $headers['Content-Type'] = 'application/json';
        $headers['User-Agent'] = 'ZAP-Webhooks/1.0';
        $headers['X-ZAP-Event-Id'] = $eventId;
        $headers['X-ZAP-Timestamp'] = (string) $timestamp;
        $headers['X-ZAP-Signature'] = $signature;

        if ($isTest) {
            $headers['X-ZAP-Test'] = 'true';
        }

        $started = hrtime(true);

        if ((bool) config('zap.webhooks.fake_delivery')) {
            return new WebhookDeliveryAttempt(
                successful: true,
                httpStatus: 200,
                durationMs: $this->durationMs($started),
                excerpt: 'fake-delivery',
            );
        }

        try {
            $response = Http::withHeaders($headers)
                ->withUserAgent('ZAP-Webhooks/1.0')
                ->withBody($rawBody, 'application/json')
                ->withOptions([
                    'allow_redirects' => false,
                    'connect_timeout' => (int) config('zap.webhooks.connect_timeout', 3),
                    'timeout' => (int) config('zap.webhooks.timeout', 10),
                    'curl' => [
                        CURLOPT_RESOLVE => [
                            sprintf('%s:%d:%s', $resolved['host'], $resolved['port'], $resolved['ip']),
                        ],
                    ],
                ])
                ->post($endpoint->url);
        } catch (ConnectionException) {
            return $this->failure(null, $started, 'connection_error');
        } catch (Throwable $exception) {
            Log::warning('Customer webhook delivery failed', [
                'webhook_endpoint_id' => $endpoint->public_id,
                'webhook_event_id' => $eventId,
                'event_type' => $eventType->value,
                'exception' => $exception::class,
            ]);

            return $this->failure(null, $started, 'transport_error');
        }

        return $this->fromResponse($response, $started);
    }

    private function fromResponse(Response $response, int $started): WebhookDeliveryAttempt
    {
        $maxBytes = max(256, (int) config('zap.webhooks.max_response_bytes', 65_536));
        $body = $response->body();
        $excerpt = mb_substr($body, 0, min(512, $maxBytes));

        return new WebhookDeliveryAttempt(
            successful: $response->successful(),
            httpStatus: $response->status(),
            durationMs: $this->durationMs($started),
            excerpt: $excerpt,
        );
    }

    private function failure(?int $httpStatus, int $started, string $excerpt): WebhookDeliveryAttempt
    {
        return new WebhookDeliveryAttempt(
            successful: false,
            httpStatus: $httpStatus,
            durationMs: $this->durationMs($started),
            excerpt: $excerpt,
        );
    }

    private function durationMs(int $started): int
    {
        return (int) max(0, (hrtime(true) - $started) / 1_000_000);
    }
}
