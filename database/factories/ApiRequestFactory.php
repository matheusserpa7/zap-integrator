<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiRequest;
use App\Models\PersonalAccessToken;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiRequest>
 */
class ApiRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'api_token_id' => PersonalAccessToken::factory(),
            'idempotency_key' => fake()->uuid(),
            'request_hash' => hash('sha256', 'fixture'),
            'response_status' => 202,
            'response_body' => ['data' => ['id' => 'msg_fixture']],
            'expires_at' => now()->addDay(),
        ];
    }
}
