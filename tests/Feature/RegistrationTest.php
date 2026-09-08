<?php

use App\Enums\WorkspaceRole;
use App\Models\EmailAllowlist;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the registration page for guests', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
});

it('rejects registration when the email is not allowlisted', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
    $this->assertDatabaseCount('workspaces', 0);
});

it('registers an allowlisted email with a workspace and owner membership', function () {
    EmailAllowlist::factory()->create(['email' => 'ada@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'Ada@Example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->public_id)->toStartWith('usr_')
        ->and($user->is_platform_admin)->toBeFalse()
        ->and($user->ownedWorkspace)->not->toBeNull()
        ->and($user->ownedWorkspace->public_id)->toStartWith('ws_')
        ->and($user->ownedWorkspace->owner_user_id)->toBe($user->id);

    $this->assertDatabaseHas('workspace_members', [
        'workspace_id' => $user->ownedWorkspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
});

it('does not register an email that is already taken', function () {
    EmailAllowlist::factory()->create(['email' => 'ada@example.com']);
    User::factory()->withWorkspace()->create(['email' => 'ada@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Ada Clone',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'ada@example.com')->count())->toBe(1);
});
