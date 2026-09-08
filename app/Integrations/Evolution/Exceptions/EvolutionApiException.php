<?php

declare(strict_types=1);

namespace App\Integrations\Evolution\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class EvolutionApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly string $correlationId,
        public readonly string $path,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response, string $correlationId, string $path): self
    {
        return new self(
            'Evolution API request failed.',
            $response->status(),
            $correlationId,
            $path,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'status' => $this->status,
            'correlation_id' => $this->correlationId,
            'path' => $this->path,
        ];
    }
}
