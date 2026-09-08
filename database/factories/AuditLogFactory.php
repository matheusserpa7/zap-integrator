<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'workspace_id' => null,
            'action' => AuditAction::AllowlistAdded,
            'target_type' => 'email_allowlist',
            'target_id' => fake()->numberBetween(1, 100),
            'target_public_id' => null,
            'request_id' => fake()->uuid(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'metadata' => ['email' => fake()->safeEmail()],
        ];
    }
}
