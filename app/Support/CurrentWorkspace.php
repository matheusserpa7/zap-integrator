<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

final class CurrentWorkspace
{
    public static function from(Request $request): ?Workspace
    {
        $workspace = $request->attributes->get('workspace');

        if ($workspace instanceof Workspace) {
            return $workspace;
        }

        $user = $request->user();

        return $user instanceof User ? $user->currentWorkspace() : null;
    }
}
