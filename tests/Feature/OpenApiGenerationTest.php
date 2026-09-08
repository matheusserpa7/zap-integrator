<?php

it('generates an OpenAPI 3.1 document for the public API', function () {
    $path = storage_path('app/openapi-test.json');

    try {
        $this->artisan('scramble:analyze')->assertSuccessful();
        $this->artisan('scramble:export', ['--path' => $path])->assertSuccessful();

        expect(is_file($path))->toBeTrue();

        /** @var array{openapi: string, paths?: array<string, mixed>} $document */
        $document = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        expect($document['openapi'])->toStartWith('3.1')
            ->and($document['paths'] ?? [])->toHaveKey('/v1/messages/text');
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});
