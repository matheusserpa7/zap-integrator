<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ConversationResource;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ConversationController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->user($request);
        abort_unless($user->can('viewAny', Conversation::class), 403);

        $workspace = $this->workspace($request);

        $conversations = $workspace->conversations()
            ->with(['contact', 'instance', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20);

        return ConversationResource::collection($conversations);
    }

    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $conversation), 404);

        $conversation->load(['contact', 'instance', 'latestMessage']);

        return ConversationResource::make($conversation);
    }

    public function messages(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $conversation), 404);

        $messages = $conversation->messages()
            ->with(['conversation', 'instance', 'media'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function workspace(Request $request): Workspace
    {
        $workspace = CurrentWorkspace::from($request);
        abort_unless($workspace instanceof Workspace, 401);

        return $workspace;
    }
}
