<?php

namespace App\Support;

use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;

final class DefaultRole
{
    public static function systemName(): string
    {
        return RoleEnum::DEFAULT->value;
    }

    public static function isDefault(?string $systemName): bool
    {
        return $systemName === self::systemName();
    }

    /**
     * @return list<string>
     */
    public static function grantedPermissionNames(): array
    {
        return [
            'read '.PermissionGroup::CHANNELS->value,
            'read '.PermissionGroup::STATISTICS->value,
            'read '.PermissionGroup::REPORTS->value,
            'read '.PermissionGroup::PLANNING->value,
            'read '.PermissionGroup::CLIENTS_AND_PROJECTS_SELF->value,
        ];
    }

    /**
     * Группы, у которых на UI можно менять только колонку «Чтение».
     *
     * @return list<string>
     */
    public static function editableGroupNames(): array
    {
        return [
            PermissionGroup::CHANNELS->value,
            PermissionGroup::STATISTICS->value,
            PermissionGroup::REPORTS->value,
            PermissionGroup::PLANNING->value,
            PermissionGroup::CLIENTS_AND_PROJECTS_SELF->value,
        ];
    }

    public static function isReadEditable(string $groupName): bool
    {
        return in_array($groupName, self::editableGroupNames(), true);
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<string>
     */
    public static function filterPermissionNames(array $permissionNames): array
    {
        return array_values(array_intersect($permissionNames, self::grantedPermissionNames()));
    }
}
