<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\ApiKeys\Actions\HandleIdempotentRequest;
use App\Domain\Messaging\Actions\AcceptOutboundText;
use App\Http\Requests\Api\V1\SendTextMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Instance;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\JsonResponse;

final class MessageController
{
    public function storeText(
        SendTextMessageRequest $request,
        HandleIdempotentRequest $idempotent,
        AcceptOutboundText $acceptText,
    ): JsonResponse {
        $user = $this->user($request);
        $workspace = $this->workspace($request);
        $token = $request->user()?->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 401);

        return $idempotent($request, $workspace, $token, function () use ($request, $user, $workspace, $acceptText): JsonResponse {
            $instance = Instance::query()
                ->where('workspace_id', $workspace->id)
                ->where('public_id', $request->string('instance_id')->toString())
                ->firstOrFail();

            abort_unless($user->can('view', $instance), 404);

            $message = $acceptText(
                $instance,
                $request->string('to')->toString(),
                $request->string('text')->toString(),
            );

            return MessageResource::make($message->load(['conversation', 'instance']))
                ->response()
                ->setStatusCode(202);
        });
    }

    private function user(SendTextMessageRequest $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function workspace(SendTextMessageRequest $request): Workspace
    {
        $workspace = CurrentWorkspace::from($request);
        abort_unless($workspace instanceof Workspace, 401);

        return $workspace;
    }
}
