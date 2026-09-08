<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InboxController
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        abort_unless($user->can('viewAny', Conversation::class), 403);

        return Inertia::render('Inbox/Index', $this->inboxProps($this->workspace($user), null));
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $conversation), 404);

        return Inertia::render('Inbox/Show', $this->inboxProps($this->workspace($user), $conversation));
    }

    /**
     * @return array<string, mixed>
     */
    private function inboxProps(Workspace $workspace, ?Conversation $selected): array
    {
        $conversations = $workspace->conversations()
            ->with(['contact', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Conversation $conversation): array => $conversation->toInertia())
            ->all();

        $messages = [];

        if ($selected instanceof Conversation) {
            $selected->load(['contact', 'instance', 'latestMessage']);
            $messages = $selected->messages()
                ->with('media')
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->get()
                ->map(fn ($message): array => $message->toInertia())
                ->all();
        }

        return [
            'conversations' => $conversations,
            'selected' => $selected instanceof Conversation ? [
                ...$selected->toInertia(),
                'instance_public_id' => $selected->instance->public_id,
            ] : null,
            'messages' => $messages,
            'pollIntervalMs' => (int) config('zap.inbox.poll_interval_ms', 4000),
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function workspace(User $user): Workspace
    {
        $workspace = $user->currentWorkspace();
        abort_unless($workspace instanceof Workspace, 403);

        return $workspace;
    }
}
