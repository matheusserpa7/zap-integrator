<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\ApiKeys\Actions\CreateApiToken;
use App\Domain\ApiKeys\Actions\RevokeApiToken;
use App\Enums\ApiAbility;
use App\Http\Requests\StoreApiTokenRequest;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ApiKeysController
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $workspace = $this->workspace($request);
        abort_unless($user->can('viewAny', [PersonalAccessToken::class, $workspace]), 403);

        $keys = PersonalAccessToken::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (PersonalAccessToken $token): array => $token->toInertia())
            ->all();

        return Inertia::render('ApiKeys/Index', [
            'keys' => $keys,
            'abilityOptions' => array_map(
                fn (ApiAbility $ability): array => [
                    'value' => $ability->value,
                    'label' => $ability->label(),
                ],
                ApiAbility::cases(),
            ),
            'canCreate' => $user->can('create', [PersonalAccessToken::class, $workspace]),
        ]);
    }

    public function store(StoreApiTokenRequest $request, CreateApiToken $createApiToken): RedirectResponse
    {
        $expiresAt = $request->date('expires_at');

        $created = $createApiToken(
            $this->user($request),
            $this->workspace($request),
            $request->string('name')->toString(),
            $request->abilities(),
            $expiresAt,
        );

        return back()->with([
            'success' => 'Chave criada. Copie agora; ela não será exibida novamente.',
            'plainTextToken' => $created->plainTextToken,
        ]);
    }

    public function destroy(Request $request, PersonalAccessToken $apiKey, RevokeApiToken $revokeApiToken): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless($user->can('delete', $apiKey), 404);

        $revokeApiToken($apiKey, $user);

        return back()->with('success', 'Chave revogada.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function workspace(Request $request): Workspace
    {
        $workspace = CurrentWorkspace::from($request);
        abort_unless($workspace instanceof Workspace, 403);

        return $workspace;
    }
}
