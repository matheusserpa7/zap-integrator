<?php

use App\Domain\ApiKeys\Actions\CreateApiToken;
use App\Domain\ApiKeys\Data\CreatedApiToken;
use App\Domain\Webhooks\Contracts\DnsResolver;
use App\Enums\ApiAbility;
use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * @return array<string, mixed>
 */
function evolutionFixture(string $name): array
{
    $path = __DIR__.'/Fixtures/Evolution/'.$name.'.json';
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Missing Evolution fixture [{$name}].");
    }

    /** @var array<string, mixed> $payload */
    $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    return $payload;
}

function postEvolutionWebhook(Instance $instance, array $payload, ?string $secret = null): TestResponse
{
    return test()->postJson(
        route('internal.webhooks.evolution', $instance->public_id),
        $payload,
        ['X-ZAP-Evolution-Secret' => $secret ?? (string) $instance->provider_webhook_secret_encrypted],
    );
}

/**
 * @param  list<string>  $abilities
 */
function createWorkspaceApiToken(
    User $user,
    array $abilities = [ApiAbility::InstancesRead->value],
    string $name = 'CI',
    ?DateTimeInterface $expiresAt = null,
    ?Workspace $workspace = null,
): CreatedApiToken {
    $workspace ??= $user->currentWorkspace();

    if (! $workspace instanceof Workspace) {
        throw new RuntimeException('The user does not have a workspace.');
    }

    return app(CreateApiToken::class)(
        $user,
        $workspace,
        $name,
        $abilities,
        $expiresAt,
    );
}

function fakePublicWebhookDns(): void
{
    app()->instance(DnsResolver::class, new class implements DnsResolver
    {
        public function resolve(string $host): array
        {
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                return [$host];
            }

            return ['93.184.216.34'];
        }
    });
}
