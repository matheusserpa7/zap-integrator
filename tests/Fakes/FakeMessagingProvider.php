<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Messaging\Data\ProviderMessage;
use App\Domain\Messaging\Data\SendTextData;
use App\Integrations\Fake\FakeMessagingProvider as AppFakeMessagingProvider;
use RuntimeException;

final class FakeMessagingProvider extends AppFakeMessagingProvider
{
    public bool $sendShouldFail = false;

    public function sendText(SendTextData $data): ProviderMessage
    {
        if ($this->sendShouldFail) {
            throw new RuntimeException('provider exploded');
        }

        return parent::sendText($data);
    }
}
