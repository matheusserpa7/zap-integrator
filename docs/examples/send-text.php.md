# Send a text message (PHP)

Comments are in English. HTTP `202 Accepted`; JSON status is `sending`.

```php
<?php

$apiKey = getenv('ZAP_API_KEY');
$instanceId = getenv('ZAP_INSTANCE_ID');

if ($apiKey === false || $instanceId === false) {
    throw new RuntimeException('Set ZAP_API_KEY and ZAP_INSTANCE_ID.');
}

$payload = json_encode([
    'instance_id' => $instanceId,
    'to' => '5511999999999',
    'text' => 'Hello from ZAP',
], JSON_THROW_ON_ERROR);

$ch = curl_init('http://localhost:8000/api/v1/messages/text');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer '.$apiKey,
        'Content-Type: application/json',
        'Idempotency-Key: '.bin2hex(random_bytes(16)),
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
]);

$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 202) {
    throw new RuntimeException("Unexpected status {$status}: {$body}");
}

/** @var array{data: array{id: string, status: string}} $decoded */
$decoded = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

echo $decoded['data']['id'].' '.$decoded['data']['status'].PHP_EOL;
```
