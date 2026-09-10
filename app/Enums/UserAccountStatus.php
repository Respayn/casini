<?php

namespace App\Enums;

enum UserAccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case PendingEmail = 'pending_email';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Активен',
            self::Inactive => 'Неактивен',
            self::PendingEmail => 'Подтвердить email',
        };
    }

    public function listLabel(): string
    {
        return match ($this) {
            self::Active => 'Активный',
            self::Inactive => 'Неактивный',
            self::PendingEmail => 'Подтвердить email',
        };
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function selectOptions(): array
    {
        return array_map(
            fn (self $status) => [
                'label' => $status->label(),
                'value' => $status->value,
            ],
            self::cases(),
        );
    }
}
