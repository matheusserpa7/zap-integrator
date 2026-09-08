<?php

declare(strict_types=1);

namespace App\Integrations\Evolution;

use App\Integrations\Evolution\Exceptions\EvolutionApiException;
use App\Support\CorrelationId;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class EvolutionClient
{
    /**
     * @return array<string, mixed>
     */
    public function createInstance(string $instanceName, string $token): array
    {
        return $this->send('POST', '/instance/create', [
            'instanceName' => $instanceName,
            'token' => $token,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function connect(string $instanceName): array
    {
        return $this->send('GET', '/instance/connect/'.$this->encode($instanceName), retry: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function connectionState(string $instanceName): array
    {
        return $this->send('GET', '/instance/connectionState/'.$this->encode($instanceName), retry: true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchInstance(string $instanceName): ?array
    {
        $payload = $this->send(
            'GET',
            '/instance/fetchInstances',
            query: ['instanceName' => $instanceName],
            retry: true,
            allowNotFound: true,
        );

        if ($payload === []) {
            return null;
        }

        if (array_is_list($payload)) {
            $first = $payload[0] ?? null;

            return is_array($first) ? $first : null;
        }

        return $payload;
    }

    public function setWebhook(string $instanceName, string $url, string $secret): void
    {
        $this->send('POST', '/webhook/set/'.$this->encode($instanceName), [
            'webhook' => [
                'enabled' => true,
                'url' => $url,
                'headers' => [
                    'X-ZAP-Evolution-Secret' => $secret,
                ],
                'byEvents' => false,
                'base64' => true,
                'events' => [
                    'QRCODE_UPDATED',
                    'CONNECTION_UPDATE',
                    'MESSAGES_UPSERT',
                    'MESSAGES_UPDATE',
                    'SEND_MESSAGE',
                ],
            ],
        ]);
    }

    public function logout(string $instanceName): void
    {
        $this->send('DELETE', '/instance/logout/'.$this->encode($instanceName), allowNotFound: true);
    }

    public function deleteInstance(string $instanceName): void
    {
        $this->send('DELETE', '/instance/delete/'.$this->encode($instanceName), allowNotFound: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $instanceName, string $to, string $text): array
    {
        return $this->send('POST', '/message/sendText/'.$this->encode($instanceName), [
            'number' => $to,
            'text' => $text,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBase64FromMediaMessage(string $instanceName, string $providerMessageId): array
    {
        return $this->send('POST', '/chat/getBase64FromMediaMessage/'.$this->encode($instanceName), [
            'message' => [
                'key' => [
                    'id' => $providerMessageId,
                ],
            ],
        ], retry: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function send(
        string $method,
        string $path,
        array $payload = [],
        array $query = [],
        bool $retry = false,
        bool $allowNotFound = false,
    ): array {
        $correlationId = CorrelationId::get();
        $started = microtime(true);

        try {
            $request = $this->baseRequest($correlationId);

            if ($retry) {
                $request = $request->retry(
                    2,
                    fn (int $attempt): int => (100 * $attempt) + random_int(0, 100),
                    $this->shouldRetry(...),
                );
            }

            $url = $query === [] ? $path : $path.'?'.http_build_query($query);

            $response = match ($method) {
                'GET' => $request->get($url),
                'POST' => $request->post($path, $payload),
                'DELETE' => $request->delete($url),
                default => throw new EvolutionApiException('Unsupported HTTP method.', 0, $correlationId, $path),
            };
        } catch (ConnectionException $exception) {
            $this->logFailure($method, $path, $correlationId, $started, 0);

            throw new EvolutionApiException(
                'Evolution API connection failed.',
                0,
                $correlationId,
                $path,
            );
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?? 0;
            $this->logFailure($method, $path, $correlationId, $started, $status);

            if ($allowNotFound && $status === 404) {
                return [];
            }

            if ($exception->response instanceof Response) {
                throw EvolutionApiException::fromResponse($exception->response, $correlationId, $path);
            }

            throw new EvolutionApiException('Evolution API request failed.', $status, $correlationId, $path);
        }

        $this->logSuccess($method, $path, $correlationId, $started, $response->status());

        if ($allowNotFound && $response->notFound()) {
            return [];
        }

        if ($response->failed()) {
            throw EvolutionApiException::fromResponse($response, $correlationId, $path);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function baseRequest(string $correlationId): PendingRequest
    {
        $apiKey = (string) config('services.evolution.api_key');

        return Http::baseUrl((string) config('services.evolution.base_url'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'apikey' => $apiKey,
                'X-Correlation-Id' => $correlationId,
            ])
            ->connectTimeout((int) config('services.evolution.connect_timeout', 3))
            ->timeout((int) config('services.evolution.request_timeout', 10))
            ->throw();
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException || $exception->response === null) {
            return false;
        }

        return $exception->response->serverError() || $exception->response->status() === 429;
    }

    private function encode(string $instanceName): string
    {
        return rawurlencode($instanceName);
    }

    private function logSuccess(string $method, string $path, string $correlationId, float $started, int $status): void
    {
        Log::info('Evolution request completed', [
            'method' => $method,
            'path' => $this->safePath($path),
            'status' => $status,
            'correlation_id' => $correlationId,
            'duration_ms' => $this->durationMs($started),
        ]);
    }

    private function logFailure(string $method, string $path, string $correlationId, float $started, int $status): void
    {
        Log::warning('Evolution request failed', [
            'method' => $method,
            'path' => $this->safePath($path),
            'status' => $status,
            'correlation_id' => $correlationId,
            'duration_ms' => $this->durationMs($started),
        ]);
    }

    private function safePath(string $path): string
    {
        return strtok($path, '?') ?: $path;
    }

    private function durationMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
