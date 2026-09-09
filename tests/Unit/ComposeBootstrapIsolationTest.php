<?php

use Symfony\Component\Yaml\Yaml;

it('skips migrate and seed on worker services so first boot does not race', function () {
    $compose = Yaml::parseFile(dirname(__DIR__, 2).'/compose.yaml');

    foreach (['horizon', 'scheduler', 'reverb'] as $service) {
        expect($compose['services'][$service]['environment']['SKIP_BOOTSTRAP'] ?? null)->toBe('true')
            ->and($compose['services'][$service]['depends_on']['app']['condition'] ?? null)->toBe('service_healthy');
    }

    expect($compose['services']['app']['environment']['SKIP_BOOTSTRAP'] ?? null)->not->toBe('true');
});
