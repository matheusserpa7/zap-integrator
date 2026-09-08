<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Actions\DeleteUser;
use App\Domain\Platform\Actions\DisableUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UserController
{
    public function index(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can('viewAny', User::class), 403);

        return Inertia::render('Platform/Users/Index', [
            'users' => User::query()
                ->orderByDesc('id')
                ->get()
                ->map(fn (User $user): array => [
                    'public_id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_platform_admin' => $user->is_platform_admin,
                    'disabled' => $user->disabled_at !== null,
                    'created_at' => $user->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function disable(Request $request, User $user, DisableUser $disableUser): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        abort_unless($actor->can('disable', $user), 403);

        $disableUser($actor, $user);

        return back()->with('success', 'Usuário desativado.');
    }

    public function destroy(Request $request, User $user, DeleteUser $deleteUser): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        abort_unless($actor->can('delete', $user), 403);

        $deleteUser($actor, $user);

        return back()->with('success', 'Usuário excluído. O e-mail poderá se cadastrar de novo se continuar na lista.');
    }
}
