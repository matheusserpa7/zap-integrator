<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApiAbility;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;
use App\Support\PublicId;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PersonalAccessToken>
 */
class PersonalAccessTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainTextToken = (string) config('zap.api.token_prefix').Str::random(40);

        return [
            'public_id' => PublicId::make('tok_'),
            'workspace_id' => Workspace::factory(),
            'tokenable_type' => User::class,
            'tokenable_id' => User::factory(),
            'name' => 'CI',
            'token' => hash('sha256', $plainTextToken),
            'token_prefix' => Str::substr($plainTextToken, 0, 13),
            'abilities' => [ApiAbility::InstancesRead->value],
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
