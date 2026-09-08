<?php

use App\Models\User;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the platform admin from configuration', function () {
    config([
        'zap.admin.email' => 'admin@zap.test',
        'zap.admin.password' => 'a-strong-admin-password',
    ]);

    $this->seed(PlatformAdminSeeder::class);

    $admin = User::query()->where('email', 'admin@zap.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->is_platform_admin)->toBeTrue()
        ->and($admin->public_id)->toStartWith('usr_');

    $this->assertTrue(Hash::check('a-strong-admin-password', $admin->password));
});

it('updates the existing platform admin instead of duplicating the row', function () {
    config([
        'zap.admin.email' => 'admin@zap.test',
        'zap.admin.password' => 'first-password-value',
    ]);

    $this->seed(PlatformAdminSeeder::class);
    $firstId = User::query()->where('email', 'admin@zap.test')->value('id');

    config(['zap.admin.password' => 'second-password-value']);
    $this->seed(PlatformAdminSeeder::class);

    expect(User::query()->where('email', 'admin@zap.test')->count())->toBe(1)
        ->and(User::query()->where('email', 'admin@zap.test')->value('id'))->toBe($firstId);

    $admin = User::query()->where('email', 'admin@zap.test')->first();

    $this->assertTrue(Hash::check('second-password-value', $admin->password));
    expect($admin->is_platform_admin)->toBeTrue();
});

it('skips seeding when the admin email is empty', function () {
    config([
        'zap.admin.email' => '',
        'zap.admin.password' => 'a-strong-admin-password',
    ]);

    $this->seed(PlatformAdminSeeder::class);

    $this->assertDatabaseCount('users', 0);
});
