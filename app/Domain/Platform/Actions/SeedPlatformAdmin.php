<?php

declare(strict_types=1);

namespace App\Domain\Platform\Actions;

use App\Models\User;
use App\Support\NormalizedEmail;
use App\Support\PublicId;

final class SeedPlatformAdmin
{
    public function __invoke(): ?User
    {
        $email = NormalizedEmail::make((string) config('zap.admin.email'));
        $password = (string) config('zap.admin.password');

        if ($email === '' || $password === '') {
            return null;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return User::query()->create([
                'public_id' => PublicId::make('usr_'),
                'name' => 'Platform Admin',
                'email' => $email,
                'password' => $password,
                'is_platform_admin' => true,
                'disabled_at' => null,
            ]);
        }

        $user->forceFill([
            'password' => $password,
            'is_platform_admin' => true,
            'disabled_at' => null,
        ])->save();

        return $user;
    }
}
