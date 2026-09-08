<?php

use App\Enums\MessageType;

it('returns placeholders for non-text inbox types', function (MessageType $type, string $placeholder) {
    expect($type->inboxBody('caption'))->toBe($placeholder);
})->with([
    'image' => [MessageType::Image, '[image]'],
    'audio' => [MessageType::Audio, '[audio]'],
    'sticker' => [MessageType::Sticker, '[sticker]'],
    'document' => [MessageType::Document, '[document]'],
    'video' => [MessageType::Video, '[video]'],
    'location' => [MessageType::Location, '[location]'],
    'contacts' => [MessageType::Contacts, '[contacts]'],
    'unknown' => [MessageType::Unknown, '[unknown]'],
]);

it('keeps the actual text for text messages', function () {
    expect(MessageType::Text->inboxBody('Olá'))->toBe('Olá');
});
