<?php

declare(strict_types=1);

namespace App\Integrations\Fake;

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
use App\Enums\ProviderConnectionStatus;
use DateTimeImmutable;

class FakeMessagingProvider implements MessagingProvider
{
    public string $downloadedBytes = 'fake-image-bytes';

    public function createInstance(CreateProviderInstanceData $data): ProviderInstance
    {
        return new ProviderInstance($data->instanceName, $data->token);
    }

    public function connectInstance(string $instanceName): QrCodeData
    {
        return new QrCodeData('data:image/png;base64,Zg==', new DateTimeImmutable('+1 minute'));
    }

    public function getConnectionState(string $instanceName): ProviderConnectionState
    {
        return new ProviderConnectionState(ProviderConnectionStatus::Connected, '5511999999999');
    }

    public function sendText(SendTextData $data): ProviderMessage
    {
        return new ProviderMessage('FAKE_SENT_'.$data->to, 'sent');
    }

    public function downloadMedia(DownloadMediaData $data): ProviderMedia
    {
        return new ProviderMedia($this->downloadedBytes, 'image/jpeg', 'photo.jpg');
    }

    public function configureWebhook(ConfigureProviderWebhookData $data): void {}

    public function disconnectInstance(string $instanceName): void {}

    public function deleteInstance(string $instanceName): void {}
}
