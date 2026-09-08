<?php

declare(strict_types=1);

namespace App\Domain\Platform\Actions;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class RecordAuditEvent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __invoke(
        AuditAction $action,
        string $targetType,
        ?int $targetId = null,
        ?string $targetPublicId = null,
        ?User $actor = null,
        ?Workspace $workspace = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();
        $requestId = $request->headers->get('X-Request-Id');

        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'workspace_id' => $workspace?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_public_id' => $targetPublicId,
            'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : (string) Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $this->withoutSecrets($metadata),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function withoutSecrets(array $metadata): array
    {
        $redacted = [];

        foreach ($metadata as $key => $value) {
            $normalized = Str::lower((string) $key);

            if (Str::contains($normalized, ['password', 'secret', 'token', 'authorization', 'api_key', 'qr'])) {
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }
}
