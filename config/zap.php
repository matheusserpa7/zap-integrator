<?php

declare(strict_types=1);

return [

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'messaging' => [
        'driver' => env('MESSAGING_DRIVER', 'evolution'),
    ],

    'quotas' => [
        'max_instances_per_workspace' => (int) env('MAX_INSTANCES_PER_WORKSPACE', 1),
    ],

    'instances' => [
        'qr_ttl_seconds' => (int) env('INSTANCE_QR_TTL_SECONDS', 60),
        'connection_sync_interval_seconds' => 15,
        'reconciliation_recent_minutes' => (int) env('INSTANCE_RECONCILIATION_RECENT_MINUTES', 360),
    ],

    'webhooks' => [
        'evolution_secret_header' => env('EVOLUTION_WEBHOOK_SECRET_HEADER', 'X-ZAP-Evolution-Secret'),
        'max_payload_bytes' => (int) env('EVOLUTION_WEBHOOK_MAX_PAYLOAD_BYTES', 2_097_152),
        'fingerprint_retention_days' => (int) env('RETENTION_WEBHOOK_PAYLOAD_DAYS', 7),
        'event_retention_days' => (int) env('RETENTION_WEBHOOK_PAYLOAD_DAYS', 7),
        'timeout' => (int) env('WEBHOOK_TIMEOUT', 10),
        'connect_timeout' => (int) env('WEBHOOK_CONNECT_TIMEOUT', 3),
        'max_response_bytes' => (int) env('WEBHOOK_MAX_RESPONSE_BYTES', 65_536),
        'max_attempts' => (int) env('WEBHOOK_MAX_RETRIES', 7),
        'jitter_seconds' => (int) env('WEBHOOK_RETRY_JITTER_SECONDS', 5),
        'retry_delays_seconds' => [30, 120, 600, 3600, 21_600, 86_400],
        'allow_http' => env('WEBHOOK_ALLOW_HTTP') === null
            ? in_array(env('APP_ENV'), ['local', 'testing', 'e2e'], true)
            : filter_var(env('WEBHOOK_ALLOW_HTTP'), FILTER_VALIDATE_BOOL),
        'allow_private_networks' => env('WEBHOOK_ALLOW_PRIVATE_NETWORKS') === null
            ? in_array(env('APP_ENV'), ['local', 'testing', 'e2e'], true)
            : filter_var(env('WEBHOOK_ALLOW_PRIVATE_NETWORKS'), FILTER_VALIDATE_BOOL),
        'fake_delivery' => filter_var(env('WEBHOOK_FAKE_DELIVERY', false), FILTER_VALIDATE_BOOL),
    ],

    'api' => [
        'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'zap_live_'),
        'rate_limit_per_token' => (int) env('RATE_LIMIT_PUBLIC_API', 60),
        'rate_limit_per_workspace' => (int) env('RATE_LIMIT_PUBLIC_API_WORKSPACE', 120),
        'idempotency_ttl_hours' => (int) env('RETENTION_IDEMPOTENCY_HOURS', 24),
    ],

    'inbox' => [
        'retention_days' => (int) env('RETENTION_INBOX_DAYS', 90),
        'poll_interval_ms' => 4000,
    ],

    'media' => [
        'disk' => env('MEDIA_DISK', 'media'),
        'retention_days' => (int) env('RETENTION_MEDIA_DAYS', 7),
    ],

];
