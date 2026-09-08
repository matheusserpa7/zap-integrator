<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_platform_admin' => false,
            'disabled_at' => null,
        ];
    }

    public function platformAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_platform_admin' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disabled_at' => now(),
        ]);
    }

    public function withWorkspace(): static
    {
        return $this->afterCreating(function (User $user): void {
            $workspace = Workspace::factory()->for($user, 'owner')->create([
                'name' => $user->name,
            ]);

            WorkspaceMember::factory()->for($workspace)->for($user)->owner()->create();
        });
    }
}
