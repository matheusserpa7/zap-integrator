<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Platform\Actions\GetDashboardMetrics;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function __invoke(Request $request, GetDashboardMetrics $metrics): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $workspace = $user->currentWorkspace();
        abort_unless($workspace !== null, 403);

        return Inertia::render('Dashboard/Index', $metrics($user, $workspace));
    }
}
