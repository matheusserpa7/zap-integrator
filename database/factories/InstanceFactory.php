<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Instances\ProviderInstanceName;
use App\Enums\InstanceProvider;
use App\Enums\InstanceStatus;
use App\Models\Instance;
use App\Models\Workspace;
use App\Support\PublicId;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instance>
 */
class InstanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->unique()->words(2, true),
            'provider' => InstanceProvider::Evolution,
            'provider_instance_name' => 'zap_pending_'.fake()->unique()->uuid(),
            'provider_instance_token_encrypted' => bin2hex(random_bytes(16)),
            'provider_webhook_secret_encrypted' => bin2hex(random_bytes(16)),
            'status' => InstanceStatus::Creating,
            'phone_number' => null,
            'qr_code_encrypted' => null,
            'qr_expires_at' => null,
            'connected_at' => null,
            'last_seen_at' => null,
            'last_error' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Instance $instance): void {
            if (blank($instance->public_id)) {
                $instance->public_id = PublicId::make('ins_');
            }

            $workspace = $instance->workspace;

            if ($workspace instanceof Workspace) {
                $instance->provider_instance_name = ProviderInstanceName::make($workspace, $instance);
            }
        })->afterCreating(function (Instance $instance): void {
            $workspace = $instance->workspace;

            if ($workspace instanceof Workspace && ! str_starts_with($instance->provider_instance_name, 'zap_'.$workspace->public_id)) {
                $instance->forceFill([
                    'provider_instance_name' => ProviderInstanceName::make($workspace, $instance),
                ])->save();
            }
        });
    }

    public function waitingQr(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InstanceStatus::WaitingQr,
            'qr_code_encrypted' => 'data:image/png;base64,cXItZml4dHVyZQ==',
            'qr_expires_at' => now()->addMinute(),
            'last_seen_at' => now(),
        ]);
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InstanceStatus::Connected,
            'phone_number' => '+5511999999999',
            'qr_code_encrypted' => null,
            'qr_expires_at' => null,
            'connected_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function disconnected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InstanceStatus::Disconnected,
            'qr_code_encrypted' => null,
            'qr_expires_at' => null,
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InstanceStatus::Error,
            'last_error' => 'Não foi possível provisionar a instância.',
        ]);
    }
}
