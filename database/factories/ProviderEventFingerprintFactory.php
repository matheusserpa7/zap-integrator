<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ZapEventType;
use App\Models\Instance;
use App\Models\ProviderEventFingerprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderEventFingerprint>
 */
class ProviderEventFingerprintFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instance_id' => Instance::factory(),
            'fingerprint' => hash('sha256', fake()->unique()->uuid()),
            'provider_event_type' => 'CONNECTION_UPDATE',
            'provider_event_id' => null,
            'event_type' => ZapEventType::InstanceConnectionUpdated,
            'payload' => ['status' => 'connected'],
            'received_at' => now(),
            'processed_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProviderEventFingerprint $fingerprint): void {
            $instance = $fingerprint->instance;

            if ($instance instanceof Instance) {
                $fingerprint->workspace_id = $instance->workspace_id;
            }
        });
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processed_at' => now(),
        ]);
    }
}
