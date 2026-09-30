<?php

namespace App\Services\Bitrix24;

use App\Enums\LaborRole;

/**
 * Колонка Каналов для часов сотрудника: ставка «аналитик» важнее роли в клиенто-проекте.
 */
final class Bitrix24LaborRoleResolver
{
    /**
     * @param  list<int>  $assistantIds
     */
    public static function resolve(
        int $userId,
        ?string $rateName,
        ?int $specialistId,
        ?int $managerId,
        array $assistantIds,
    ): ?LaborRole {
        if ($rateName !== null && mb_stripos($rateName, 'аналитик') !== false) {
            return LaborRole::Analyst;
        }

        if ($specialistId === $userId) {
            return LaborRole::SeoSpecialist;
        }

        if ($managerId === $userId) {
            return LaborRole::OrkManager;
        }

        if (in_array($userId, $assistantIds, true)) {
            return LaborRole::SeoAssistant;
        }

        return null;
    }
}
