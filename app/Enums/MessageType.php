<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case Sticker = 'sticker';
    case Document = 'document';
    case Location = 'location';
    case Contacts = 'contacts';
    case Unknown = 'unknown';

    public function placeholder(): string
    {
        return match ($this) {
            self::Text => '',
            self::Image => '[image]',
            self::Audio => '[audio]',
            self::Video => '[video]',
            self::Sticker => '[sticker]',
            self::Document => '[document]',
            self::Location => '[location]',
            self::Contacts => '[contacts]',
            self::Unknown => '[unknown]',
        };
    }

    public function hasMedia(): bool
    {
        return in_array($this, [
            self::Image,
            self::Audio,
            self::Video,
            self::Sticker,
            self::Document,
        ], true);
    }

    public function inboxBody(?string $text): string
    {
        if ($this === self::Text) {
            return $text ?? '';
        }

        return $this->placeholder();
    }
}
