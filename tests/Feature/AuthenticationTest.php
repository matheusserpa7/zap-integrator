<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the login page for guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
});

it('authenticates an active user with a session', function () {
    $user = User::factory()->withWorkspace()->create([
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'Owner@Example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('does not authenticate a disabled user', function () {
    User::factory()->disabled()->withWorkspace()->create([
        'email' => 'disabled@example.com',
        'password' => 'password',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'disabled@example.com',
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects invalid credentials', function () {
    User::factory()->withWorkspace()->create([
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs the user out and invalidates the session', function () {
    $user = User::factory()->withWorkspace()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('redirects guests away from the authenticated app', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('redirects platform admins without a workspace to the allowlist after login', function () {
    User::factory()->platformAdmin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertRedirect(route('platform.allowlist.index'));
});
