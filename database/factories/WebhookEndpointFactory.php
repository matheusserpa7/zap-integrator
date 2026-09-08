<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'CRM '.fake()->unique()->word(),
            'url' => 'https://example.com/webhooks/zap',
            'description' => null,
            'event_type' => ZapEventType::MessageReceived,
            'payload_mode' => WebhookPayloadMode::Canonical,
            'body_mapping' => [],
            'headers_mapping_encrypted' => [],
            'secret_encrypted' => bin2hex(random_bytes(32)),
            'enabled' => true,
            'failure_count' => 0,
        ];
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payload_mode' => WebhookPayloadMode::Custom,
            'body_mapping' => [
                ['path' => 'customer.phone', 'value_mode' => 'field', 'value' => 'data.from'],
                ['path' => 'source', 'value_mode' => 'fixed', 'value' => 'zap'],
                ['path' => 'reference', 'value_mode' => 'expression', 'value' => 'zap-{{ message.id }}'],
            ],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'enabled' => false,
        ]);
    }
}
