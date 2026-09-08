<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endpoint = WebhookEndpoint::factory();

        return [
            'workspace_id' => Workspace::factory(),
            'webhook_event_id' => WebhookEvent::factory(),
            'webhook_endpoint_id' => $endpoint,
            'attempt' => 0,
            'status' => WebhookDeliveryStatus::Pending,
            'http_status' => null,
            'response_excerpt' => null,
            'duration_ms' => null,
            'next_retry_at' => null,
            'delivered_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (WebhookDelivery $delivery): void {
            $endpoint = $delivery->endpoint;

            if ($endpoint instanceof WebhookEndpoint) {
                $delivery->workspace_id = $endpoint->workspace_id;
            }
        })->afterCreating(function (WebhookDelivery $delivery): void {
            $endpoint = $delivery->endpoint;

            if ($endpoint instanceof WebhookEndpoint && $delivery->workspace_id !== $endpoint->workspace_id) {
                $delivery->forceFill(['workspace_id' => $endpoint->workspace_id])->save();
            }
        });
    }

    public function retrying(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt' => 2,
            'status' => WebhookDeliveryStatus::Retrying,
            'http_status' => 500,
            'response_excerpt' => 'retry',
            'duration_ms' => 20,
            'next_retry_at' => now()->addMinutes(2),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt' => 1,
            'status' => WebhookDeliveryStatus::Delivered,
            'http_status' => 200,
            'response_excerpt' => 'ok',
            'duration_ms' => 12,
            'delivered_at' => now(),
        ]);
    }

    public function deadLetter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt' => 7,
            'status' => WebhookDeliveryStatus::DeadLetter,
            'http_status' => 500,
            'response_excerpt' => 'error',
            'duration_ms' => 40,
        ]);
    }
}
