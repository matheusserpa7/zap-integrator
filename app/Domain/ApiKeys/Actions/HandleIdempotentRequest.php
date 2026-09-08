<?php

declare(strict_types=1);

namespace App\Domain\ApiKeys\Actions;

use App\Domain\ApiKeys\Exceptions\IdempotencyKeyConflict;
use App\Domain\ApiKeys\Exceptions\IdempotencyKeyRequired;
use App\Models\ApiRequest;
use App\Models\PersonalAccessToken;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class HandleIdempotentRequest
{
    /**
     * @param  Closure(): JsonResponse  $callback
     */
    public function __invoke(Request $request, Workspace $workspace, PersonalAccessToken $token, Closure $callback): JsonResponse
    {
        $key = $request->headers->get('Idempotency-Key');

        if (! is_string($key) || trim($key) === '') {
            throw new IdempotencyKeyRequired;
        }

        $key = trim($key);
        $hash = $this->requestHash($request);
        $ttlHours = max(1, (int) config('zap.api.idempotency_ttl_hours', 24));

        $record = DB::transaction(function () use ($workspace, $token, $key, $hash, $ttlHours): ApiRequest {
            $existing = ApiRequest::query()
                ->where('workspace_id', $workspace->id)
                ->where('api_token_id', $token->id)
                ->where('idempotency_key', $key)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof ApiRequest) {
                if ($existing->request_hash !== $hash) {
                    throw new IdempotencyKeyConflict;
                }

                return $existing;
            }

            return ApiRequest::query()->create([
                'workspace_id' => $workspace->id,
                'api_token_id' => $token->id,
                'idempotency_key' => $key,
                'request_hash' => $hash,
                'expires_at' => now()->addHours($ttlHours),
            ]);
        });

        if ($record->isComplete()) {
            return response()->json($record->response_body, (int) $record->response_status);
        }

        $response = $callback();
        /** @var array<string, mixed> $payload */
        $payload = $response->getData(true);

        $record->forceFill([
            'response_status' => $response->getStatusCode(),
            'response_body' => $payload,
        ])->save();

        return $response;
    }

    private function requestHash(Request $request): string
    {
        $content = $request->getContent();
        $decoded = json_decode($content, true);
        $canonical = is_array($decoded)
            ? (string) json_encode($this->sort($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $content;

        return hash('sha256', strtoupper($request->getMethod())."\n".$request->path()."\n".$canonical);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function sort(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sort($item);
            }
        }

        ksort($value);

        return $value;
    }
}
