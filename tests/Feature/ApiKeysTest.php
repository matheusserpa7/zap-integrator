<?php

use App\Enums\ApiAbility;
use App\Enums\AuditAction;
use App\Enums\WorkspaceRole;
use App\Models\AuditLog;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\WorkspaceMember;
use Inertia\Testing\AssertableInertia as Assert;

it('renders api keys for a workspace owner', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('api-keys.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ApiKeys/Index')
            ->has('keys', 0)
            ->has('abilityOptions', count(ApiAbility::cases()))
            ->where('canCreate', true));
});

it('returns the plaintext token once and never stores it', function () {
    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->post(route('api-keys.store'), [
            'name' => 'Integração CI',
            'abilities' => [ApiAbility::InstancesRead->value],
        ])
        ->assertRedirect();

    $plainTextToken = session('plainTextToken');

    expect($plainTextToken)->toBeString()->toStartWith('zap_live_')
        ->and($plainTextToken)->not->toContain('|');

    $this->actingAs($owner)
        ->get(route('api-keys.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ApiKeys/Index')
            ->where('flash.plainTextToken', $plainTextToken)
            ->has('keys', 1)
            ->where('keys.0.name', 'Integração CI')
            ->missing('keys.0.token'));

    $this->actingAs($owner)
        ->get(route('api-keys.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('flash.plainTextToken', null)
            ->has('keys', 1)
            ->missing('keys.0.token'));

    $token = PersonalAccessToken::query()->first();

    expect($token)->not->toBeNull()
        ->and($token?->token)->toBe(hash('sha256', $plainTextToken))
        ->and($token?->token)->not->toBe($plainTextToken)
        ->and($token?->token_prefix)->toStartWith('zap_live_');

    $audit = AuditLog::query()->where('action', AuditAction::ApiTokenCreated)->first();

    expect($audit)->not->toBeNull()
        ->and($audit?->metadata)->not->toHaveKey('plainTextToken')
        ->and($audit?->metadata)->not->toHaveKey('token')
        ->and(json_encode($audit?->metadata))->not->toContain($plainTextToken);
});

it('revokes a token and records an audit event without the secret', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesRead->value], 'Revogar');

    $this->actingAs($owner)
        ->delete(route('api-keys.destroy', $created->token))
        ->assertRedirect();

    $this->assertModelMissing($created->token);

    $audit = AuditLog::query()->where('action', AuditAction::ApiTokenRevoked)->first();

    expect($audit)->not->toBeNull()
        ->and($audit?->target_public_id)->toBe($created->token->public_id)
        ->and(json_encode($audit?->metadata))->not->toContain($created->plainTextToken);
});

it('validates that a name and at least one ability are required', function () {
    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->from(route('api-keys.index'))
        ->post(route('api-keys.store'), [
            'name' => '',
            'abilities' => [],
        ])
        ->assertRedirect(route('api-keys.index'))
        ->assertSessionHasErrors([
            'name' => 'Informe um nome para a chave.',
            'abilities' => 'Selecione pelo menos uma permissão.',
        ]);

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('forbids a viewer from managing api keys', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);

    $this->actingAs($viewer)
        ->get(route('api-keys.index'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('api-keys.store'), [
            'name' => 'Viewer',
            'abilities' => [ApiAbility::InstancesRead->value],
        ])
        ->assertForbidden();
});
