<?php

declare(strict_types=1);

namespace App\Domain\Conversations\Actions;

use App\Domain\Conversations\NormalizeWaId;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Instance;
use Illuminate\Support\Facades\DB;

final class ResolveDirectConversation
{
    public function __construct(private NormalizeWaId $normalizeWaId) {}

    public function __invoke(Instance $instance, string $peerId, ?string $displayName = null): Conversation
    {
        $waId = ($this->normalizeWaId)($peerId);

        return DB::transaction(function () use ($instance, $waId, $displayName): Conversation {
            $contact = Contact::query()->firstOrCreate(
                [
                    'instance_id' => $instance->id,
                    'wa_id' => $waId,
                ],
                [
                    'workspace_id' => $instance->workspace_id,
                    'display_name' => $this->usableName($displayName),
                ],
            );

            if ($displayName !== null && $this->usableName($displayName) !== null && $contact->display_name === null) {
                $contact->forceFill(['display_name' => $this->usableName($displayName)])->save();
            }

            return Conversation::query()->firstOrCreate(
                [
                    'instance_id' => $instance->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'workspace_id' => $instance->workspace_id,
                ],
            );
        });
    }

    private function usableName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $trimmed = trim($name);

        return $trimmed === '' ? null : $trimmed;
    }
}
