<?php

test('the messaging provider contract does not mention Evolution types', function () {
    $source = (string) file_get_contents(base_path('app/Domain/Messaging/Contracts/MessagingProvider.php'));

    expect($source)
        ->not->toContain('Evolution')
        ->and($source)->not->toContain('QRCODE')
        ->and($source)->not->toContain('http');
});
