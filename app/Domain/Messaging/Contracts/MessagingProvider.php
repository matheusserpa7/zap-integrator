<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Contracts;

use App\Domain\Messaging\Data\ConfigureProviderWebhookData;
use App\Domain\Messaging\Data\CreateProviderInstanceData;
use App\Domain\Messaging\Data\DownloadMediaData;
use App\Domain\Messaging\Data\ProviderConnectionState;
use App\Domain\Messaging\Data\ProviderInstance;
use App\Domain\Messaging\Data\ProviderMedia;
use App\Domain\Messaging\Data\ProviderMessage;
use App\Domain\Messaging\Data\QrCodeData;
use App\Domain\Messaging\Data\SendTextData;

interface MessagingProvider
{
    public function createInstance(CreateProviderInstanceData $data): ProviderInstance;

    public function connectInstance(string $instanceName): QrCodeData;

    public function getConnectionState(string $instanceName): ProviderConnectionState;

    public function sendText(SendTextData $data): ProviderMessage;

    public function downloadMedia(DownloadMediaData $data): ProviderMedia;

    public function configureWebhook(ConfigureProviderWebhookData $data): void;

    public function disconnectInstance(string $instanceName): void;

    public function deleteInstance(string $instanceName): void;
}
