<?php

namespace App\Enums;

use App\Models\User;

enum UserAccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case PendingEmail = 'pending_email';

    public static function fromFlags(bool $isActive, mixed $emailVerifiedAt): self
    {
        if ($isActive) {
            return self::Active;
        }

        return $emailVerifiedAt === null
            ? self::PendingEmail
            : self::Inactive;
    }

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
     * @return array{is_active: bool, email_verified_at?: mixed}
     */
    public function toPersistence(?User $existing = null): array
    {
        return match ($this) {
            self::Active => [
                'is_active' => true,
            ],
            self::Inactive => [
                'is_active' => false,
                // Иначе при null verified_at статус неотличим от «Подтвердить email»
                ...(($existing === null || $existing->email_verified_at === null)
                    ? ['email_verified_at' => now()]
                    : []),
            ],
            self::PendingEmail => [
                'is_active' => false,
                'email_verified_at' => null,
            ],
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
