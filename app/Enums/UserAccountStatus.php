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
     * Варианты для формы. «Подтвердить email» служебный: в список выбора не входит.
     * Если он уже стоит у пользователя, подпись остаётся в закрытом поле.
     *
     * @return list<array{label: string, value: string, selectable: bool}>
     */
    public static function selectOptions(?string $current = null): array
    {
        $statuses = [self::Active, self::Inactive];

        if ($current === self::PendingEmail->value) {
            $statuses[] = self::PendingEmail;
        }

        return array_map(
            fn (self $status) => [
                'label' => $status->label(),
                'value' => $status->value,
                'selectable' => $status !== self::PendingEmail,
            ],
            $statuses,
        );
    }
}
