<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WorkspaceController
{
    public function __invoke(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->can('view', $workspace), 404);

        return redirect()->route('dashboard');
    }
}
