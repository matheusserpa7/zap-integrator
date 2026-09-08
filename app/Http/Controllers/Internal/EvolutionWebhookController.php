<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Domain\Webhooks\Actions\IngestProviderWebhook;
use App\Http\Controllers\Controller;
use App\Integrations\Evolution\NormalizeEvolutionWebhook;
use App\Models\Instance;
use App\Support\ConstantTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class EvolutionWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $instancePublicId,
        NormalizeEvolutionWebhook $normalize,
        IngestProviderWebhook $ingest,
    ): Response {
        $maxBytes = (int) config('zap.webhooks.max_payload_bytes', 2_097_152);
        $body = $request->getContent();

        if (strlen($body) > $maxBytes) {
            abort(413);
        }

        $instance = Instance::query()->where('public_id', $instancePublicId)->first();

        if ($instance === null) {
            abort(404);
        }

        $header = (string) config('zap.webhooks.evolution_secret_header', 'X-ZAP-Evolution-Secret');
        $expected = (string) $instance->provider_webhook_secret_encrypted;
        $provided = (string) $request->header($header, '');

        if ($expected === '' || ! ConstantTime::equals($expected, $provided)) {
            abort(401);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        if ($payload === []) {
            abort(400);
        }

        $ingest($instance, $normalize($payload));

        return response()->noContent(202);
    }
}
