<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Actions\AllowlistEmail;
use App\Domain\Platform\Actions\RemoveAllowlistedEmail;
use App\Http\Requests\Platform\StoreAllowlistRequest;
use App\Models\EmailAllowlist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AllowlistController
{
    public function index(Request $request): Response
    {
        $this->authorize($request, 'viewAny');

        return Inertia::render('Platform/Allowlist/Index', [
            'emails' => EmailAllowlist::query()
                ->orderByDesc('id')
                ->get()
                ->map(fn (EmailAllowlist $entry): array => [
                    'email' => $entry->email,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(StoreAllowlistRequest $request, AllowlistEmail $allowlistEmail): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $allowlistEmail($request->string('email')->toString(), $actor);

        return back()->with('success', 'E-mail adicionado à lista de acesso.');
    }

    public function destroy(Request $request, EmailAllowlist $emailAllowlist, RemoveAllowlistedEmail $removeAllowlistedEmail): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        abort_unless($actor->can('delete', $emailAllowlist), 403);

        $removeAllowlistedEmail($emailAllowlist, $actor);

        return back()->with('success', 'E-mail removido da lista de acesso.');
    }

    private function authorize(Request $request, string $ability): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can($ability, EmailAllowlist::class), 403);
    }
}
