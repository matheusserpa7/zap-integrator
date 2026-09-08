<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Webhooks\Actions\IngestProviderWebhook;
use App\Integrations\Evolution\NormalizeEvolutionWebhook;
use App\Models\Instance;
use Illuminate\Console\Command;
use JsonException;
use RuntimeException;

final class IngestE2eProviderFixture extends Command
{
    protected $signature = 'e2e:ingest-provider {instance : Instance public id} {fixture : Fixture file stem under tests/Fixtures/Evolution}';

    protected $description = 'Ingest a committed Evolution fixture as if the provider posted it (e2e and testing only).';

    protected $hidden = true;

    public function handle(NormalizeEvolutionWebhook $normalize, IngestProviderWebhook $ingest): int
    {
        if (! app()->environment(['e2e', 'testing'])) {
            $this->error('This command is only available in the e2e and testing environments.');

            return self::FAILURE;
        }

        $instance = Instance::query()->where('public_id', (string) $this->argument('instance'))->first();

        if (! $instance instanceof Instance) {
            $this->error('Instance not found.');

            return self::FAILURE;
        }

        $payload = $this->fixturePayload((string) $this->argument('fixture'));
        $ingest($instance, $normalize($payload));

        $this->info('Fixture ingested.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function fixturePayload(string $name): array
    {
        if (preg_match('/^[a-z0-9-]+$/', $name) !== 1) {
            throw new RuntimeException('Invalid fixture name.');
        }

        $path = base_path('tests/Fixtures/Evolution/'.$name.'.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Missing Evolution fixture [{$name}].");
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid Evolution fixture [{$name}].", previous: $exception);
        }

        return $payload;
    }
}
