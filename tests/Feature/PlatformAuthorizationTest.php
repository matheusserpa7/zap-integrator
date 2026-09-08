<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\EmailAllowlist;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('forbids non-admins from opening platform routes', function () {
    $user = User::factory()->withWorkspace()->create();

    $this->actingAs($user)
        ->get(route('platform.allowlist.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('platform.users.index'))
        ->assertForbidden();
});

it('allows a platform admin to add and remove an allowlisted email', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->get(route('platform.allowlist.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Platform/Allowlist/Index'));

    $this->actingAs($admin)
        ->post(route('platform.allowlist.store'), [
            'email' => 'New.User@Example.com',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('email_allowlists', [
        'email' => 'new.user@example.com',
        'created_by_user_id' => $admin->id,
    ]);

    expect(AuditLog::query()->where('action', AuditAction::AllowlistAdded)->count())->toBe(1);

    $entry = EmailAllowlist::query()->where('email', 'new.user@example.com')->first();

    $this->actingAs($admin)
        ->delete(route('platform.allowlist.destroy', $entry))
        ->assertRedirect();

    $this->assertDatabaseMissing('email_allowlists', ['email' => 'new.user@example.com']);
    expect(AuditLog::query()->where('action', AuditAction::AllowlistRemoved)->count())->toBe(1);
});

it('allows a platform admin to disable and delete another user', function () {
    $admin = User::factory()->platformAdmin()->create();
    $member = User::factory()->withWorkspace()->create(['email' => 'member@example.com']);

    $this->actingAs($admin)
        ->get(route('platform.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Platform/Users/Index')
            ->has('users'));

    $this->actingAs($admin)
        ->post(route('platform.users.disable', $member))
        ->assertRedirect();

    expect($member->fresh()->disabled_at)->not->toBeNull();
    expect(AuditLog::query()->where('action', AuditAction::UserDisabled)->count())->toBe(1);

    $this->actingAs($admin)
        ->delete(route('platform.users.destroy', $member))
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['email' => 'member@example.com']);
    expect(AuditLog::query()->where('action', AuditAction::UserDeleted)->count())->toBe(1);
});

it('does not let a platform admin disable themselves', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->post(route('platform.users.disable', $admin))
        ->assertForbidden();

    expect($admin->fresh()->disabled_at)->toBeNull();
});

it('lets an allowlisted email register again after the platform admin deletes the user', function () {
    $admin = User::factory()->platformAdmin()->create();
    EmailAllowlist::factory()->create([
        'email' => 'ada@example.com',
        'created_by_user_id' => $admin->id,
    ]);
    $member = User::factory()->withWorkspace()->create(['email' => 'ada@example.com']);

    $this->actingAs($admin)
        ->delete(route('platform.users.destroy', $member))
        ->assertRedirect();

    $this->post(route('logout'));

    $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'ada@example.com')->count())->toBe(1);
});

it('does not store secrets in audit metadata', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->post(route('platform.allowlist.store'), [
            'email' => 'safe@example.com',
            'password' => 'should-not-be-stored',
        ])
        ->assertRedirect();

    $log = AuditLog::query()->first();

    expect($log->metadata)->toHaveKey('email')
        ->and($log->metadata)->not->toHaveKey('password');
});
