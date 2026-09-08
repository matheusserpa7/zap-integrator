<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ZapEventType;
use App\Models\Instance;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $payload = [
            'id' => 'evt_fixture',
            'type' => ZapEventType::MessageReceived->value,
            'data' => ['type' => 'text', 'text' => 'oi'],
        ];

        return [
            'workspace_id' => Workspace::factory(),
            'instance_id' => Instance::factory(),
            'type' => ZapEventType::MessageReceived,
            'provider_event_type' => 'MESSAGES_UPSERT',
            'provider_event_id' => fake()->uuid(),
            'payload' => $payload,
            'payload_hash' => hash('sha256', (string) json_encode($payload)),
            'occurred_at' => now(),
            'received_at' => now(),
            'expires_at' => now()->addDays(7),
        ];
    }
}
