<?php

use App\Domain\Webhooks\SignWebhookRequest;

it('creates a verifiable hmac over timestamp and raw body', function () {
    $signer = new SignWebhookRequest;
    $secret = str_repeat('ab', 32);
    $timestamp = 1_788_786_000;
    $body = '{"hello":"world"}';

    $header = $signer->signature($secret, $timestamp, $body);

    expect($header)->toStartWith('sha256=')
        ->and($signer->verify($secret, $timestamp, $body, $header))->toBeTrue()
        ->and($signer->verify($secret, $timestamp, '{"hello":"nope"}', $header))->toBeFalse();
});
