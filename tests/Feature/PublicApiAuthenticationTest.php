<?php

use App\Enums\ApiAbility;
use App\Enums\WorkspaceRole;
use App\Models\Instance;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\WorkspaceMember;

it('returns 401 when no bearer token is provided', function () {
    $this->getJson(route('api.v1.instances.index'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated')
        ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
});

it('returns 401 when a session cookie is used instead of a bearer token', function () {
    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->getJson(route('api.v1.instances.index'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('returns 401 when the bearer token has been revoked', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner);

    $created->token->delete();

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('returns 401 when the bearer token has expired', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, expiresAt: now()->subMinute());

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('returns 403 when the token is missing the required ability', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'missing_ability');
});

it('updates last_used_at on a successful request', function () {
    $this->freezeTime();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner);

    expect($created->token->last_used_at)->toBeNull();

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertOk();

    expect($created->token->fresh()?->last_used_at?->getTimestamp())->toBe(now()->getTimestamp());
});

it('returns 429 with the public error envelope when the token is rate limited', function () {
    config([
        'zap.api.rate_limit_per_token' => 1,
        'zap.api.rate_limit_per_workspace' => 100,
    ]);

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertOk();

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'rate_limited')
        ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
});

it('does not send permissive cors headers for the public api', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner);

    $this->withToken($created->plainTextToken)
        ->withHeaders(['Origin' => 'https://evil.example'])
        ->getJson(route('api.v1.instances.index'))
        ->assertOk()
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});

it('does not treat a sequential id pipe token as valid', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner);
    $forged = $created->token->id.'|'.$created->plainTextToken;

    $this->withToken($forged)
        ->getJson(route('api.v1.instances.index'))
        ->assertUnauthorized();

    expect(PersonalAccessToken::findToken($created->plainTextToken)?->is($created->token))->toBeTrue()
        ->and(PersonalAccessToken::findToken($forged))->toBeNull();
});

it('keeps platform admin status independent of token abilities', function () {
    $admin = User::factory()->platformAdmin()->withWorkspace()->create();
    $created = createWorkspaceApiToken($admin, [ApiAbility::InstancesRead->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.store'), ['name' => 'API'])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'missing_ability');
});

it('still enforces workspace policies when the token has the write ability', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);

    $created = createWorkspaceApiToken(
        $viewer,
        [ApiAbility::InstancesWrite->value],
        'Viewer write',
        null,
        $workspace,
    );

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.store'), ['name' => 'API'])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');

    expect(Instance::query()->count())->toBe(0);
    expect(PersonalAccessToken::query()->count())->toBe(1);
});
