<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('exposes application health without depending on Evolution', function () {
    $this->get('/up')->assertOk();
});

it('redirects guests from the dashboard to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders the dashboard shell for a workspace owner', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Index'));
});

it('renders the read-only inbox', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Inbox/Index'));
});

it('renders the instances index for a workspace owner', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('instances.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Instances/Index'));
});

it('renders the webhook index and builder', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('webhooks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Webhooks/Index'));
});

it('renders the api keys placeholder', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('api-keys.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('ApiKeys/Index'));
});
