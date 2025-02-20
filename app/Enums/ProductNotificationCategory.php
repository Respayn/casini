<?php

namespace App\Enums;

enum ProductNotificationCategory: string
{
    case IMPORTANT = 'important';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IMPORTANT => 'Важные',
            self::OTHER => 'Остальные'
        };
    }
}
