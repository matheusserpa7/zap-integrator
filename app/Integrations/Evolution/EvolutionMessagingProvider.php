<?php

declare(strict_types=1);

namespace App\Integrations\Evolution;

use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Data\ConfigureProviderWebhookData;
use App\Domain\Messaging\Data\CreateProviderInstanceData;
use App\Domain\Messaging\Data\DownloadMediaData;
use App\Domain\Messaging\Data\ProviderConnectionState;
use App\Domain\Messaging\Data\ProviderInstance;
use App\Domain\Messaging\Data\ProviderMedia;
use App\Domain\Messaging\Data\ProviderMessage;
use App\Domain\Messaging\Data\QrCodeData;
use App\Domain\Messaging\Data\SendTextData;
use App\Domain\Messaging\Exceptions\ProviderAuthenticationFailed;
use App\Domain\Messaging\Exceptions\ProviderRateLimited;
use App\Domain\Messaging\Exceptions\ProviderUnavailable;
use App\Enums\ProviderConnectionStatus;
use App\Integrations\Evolution\Exceptions\EvolutionApiException;
use DateTimeImmutable;
use Illuminate\Support\Arr;

/**
 * Evolution implementation of the ZAP messaging seam.
 */
final class EvolutionMessagingProvider implements MessagingProvider
{
    public function __construct(private EvolutionClient $client) {}

    public function createInstance(CreateProviderInstanceData $data): ProviderInstance
    {
        try {
            $payload = $this->client->createInstance($data->instanceName, $data->token);
        } catch (EvolutionApiException $exception) {
            if (in_array($exception->status, [403, 409], true) && $this->client->fetchInstance($data->instanceName) !== null) {
                return new ProviderInstance($data->instanceName, $data->token);
            }

            throw $this->mapException($exception);
        }

        return new ProviderInstance(
            instanceName: $data->instanceName,
            token: $this->extractToken($payload) ?? $data->token,
        );
    }

    public function connectInstance(string $instanceName): QrCodeData
    {
        try {
            $payload = $this->client->connect($instanceName);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }

        $image = $this->extractQrImage($payload);

        if ($image === null) {
            throw new ProviderUnavailable;
        }

        $ttl = (int) config('zap.instances.qr_ttl_seconds', 60);

        return new QrCodeData(
            image: $image,
            expiresAt: (new DateTimeImmutable)->modify('+'.$ttl.' seconds'),
        );
    }

    public function getConnectionState(string $instanceName): ProviderConnectionState
    {
        try {
            $payload = $this->client->connectionState($instanceName);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }

        $state = strtolower((string) Arr::get($payload, 'instance.state', Arr::get($payload, 'state', 'close')));
        $phone = Arr::get($payload, 'instance.owner')
            ?? Arr::get($payload, 'instance.wuid')
            ?? Arr::get($payload, 'instance.phoneNumber');

        return new ProviderConnectionState(
            status: $this->mapConnectionState($state),
            phoneNumber: is_string($phone) && $phone !== '' ? $phone : null,
        );
    }

    public function sendText(SendTextData $data): ProviderMessage
    {
        try {
            $payload = $this->client->sendText($data->instanceName, $data->to, $data->text);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }

        $id = Arr::get($payload, 'key.id')
            ?? Arr::get($payload, 'keyId')
            ?? Arr::get($payload, 'id');
        $status = Arr::get($payload, 'status', 'pending');

        return new ProviderMessage(
            providerMessageId: is_string($id) && $id !== '' ? $id : '',
            status: is_string($status) && $status !== '' ? strtolower($status) : 'pending',
        );
    }

    public function downloadMedia(DownloadMediaData $data): ProviderMedia
    {
        try {
            $payload = $this->client->getBase64FromMediaMessage($data->instanceName, $data->providerMessageId);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }

        $encoded = Arr::get($payload, 'base64') ?? Arr::get($payload, 'base64Data');

        if (! is_string($encoded) || $encoded === '') {
            throw new ProviderUnavailable;
        }

        if (str_contains($encoded, ',')) {
            $encoded = (string) substr($encoded, strpos($encoded, ',') + 1);
        }

        $bytes = base64_decode($encoded, true);

        if ($bytes === false) {
            throw new ProviderUnavailable;
        }

        $mime = Arr::get($payload, 'mimetype') ?? Arr::get($payload, 'mimeType') ?? 'application/octet-stream';
        $filename = Arr::get($payload, 'fileName') ?? Arr::get($payload, 'filename');

        return new ProviderMedia(
            contents: $bytes,
            mimeType: is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream',
            filename: is_string($filename) && $filename !== '' ? $filename : null,
        );
    }

    public function configureWebhook(ConfigureProviderWebhookData $data): void
    {
        try {
            $this->client->setWebhook($data->instanceName, $data->url, $data->secret);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }
    }

    public function disconnectInstance(string $instanceName): void
    {
        try {
            $this->client->logout($instanceName);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }
    }

    public function deleteInstance(string $instanceName): void
    {
        try {
            $this->client->deleteInstance($instanceName);
        } catch (EvolutionApiException $exception) {
            throw $this->mapException($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractToken(array $payload): ?string
    {
        $hash = Arr::get($payload, 'hash');

        if (is_string($hash) && $hash !== '') {
            return $hash;
        }

        $apiKey = Arr::get($payload, 'hash.apikey');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractQrImage(array $payload): ?string
    {
        $candidates = [
            Arr::get($payload, 'qrcode.base64'),
            Arr::get($payload, 'base64'),
            Arr::get($payload, 'qrcode.qrcode'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if (str_starts_with($candidate, 'data:image/')) {
                return $candidate;
            }

            return 'data:image/png;base64,'.$candidate;
        }

        return null;
    }

    private function mapConnectionState(string $state): ProviderConnectionStatus
    {
        return match ($state) {
            'open' => ProviderConnectionStatus::Connected,
            'connecting' => ProviderConnectionStatus::Connecting,
            default => ProviderConnectionStatus::Disconnected,
        };
    }

    private function mapException(EvolutionApiException $exception): ProviderAuthenticationFailed|ProviderRateLimited|ProviderUnavailable
    {
        return match (true) {
            in_array($exception->status, [401, 403], true) => new ProviderAuthenticationFailed,
            $exception->status === 429 => new ProviderRateLimited,
            default => new ProviderUnavailable,
        };
    }
}
