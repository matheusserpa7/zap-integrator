<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Data\WebhookDeliveryAttempt;
use App\Domain\Webhooks\PostSignedWebhook;
use App\Domain\Webhooks\WebhookEventCatalog;
use App\Models\WebhookEndpoint;

final class TestWebhookEndpoint
{
    public function __construct(
        private PostSignedWebhook $post,
        private WebhookEventCatalog $catalog,
    ) {}

    public function __invoke(WebhookEndpoint $endpoint): WebhookDeliveryAttempt
    {
        $fixture = $this->catalog->fixture($endpoint->event_type);

        return ($this->post)(
            $endpoint,
            $fixture,
            is_string($fixture['id'] ?? null) ? $fixture['id'] : 'evt_test',
            $endpoint->event_type,
            isTest: true,
        );
    }
}
