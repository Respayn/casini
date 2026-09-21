<?php

namespace App\Enums;

enum PermissionGroup: string
{
    case CHANNELS = 'channels';
    case STATISTICS = 'statistics';
    case CLIENTS_AND_PROJECTS = 'clients and projects';
    case STATISTICS_SETTLEMENT = 'statistics settlement';
    case CLIENTS_AND_PROJECTS_SELF = 'clients and projects self';
    case CLIENTS_AND_PROJECTS_ALL = 'clients and projects all';
    case BUDGET_RECONCILIATION = 'budget reconciliation';
    case ADVERTISING_FUNDS_MOVEMENT = 'advertising funds movement';
    case ADVERTISING_FUNDS_MOVEMENT_STATUS = 'advertising funds movement status';
    case ADVERTISING_FUNDS_MOVEMENT_INVOICE = 'advertising funds movement invoice';
    case PLANNING = 'planning';
    case PLANNING_APPROVAL = 'planning approval';
    case MEDIA_PLANNING = 'media planning';
    case REPORTS = 'reports';
    case REPORT_TEMPLATES = 'report templates';
    case SYSTEM_SETTINGS = 'system settings';
    case SYSTEM_SETTINGS_DICTIONARIES = 'system settings dictionaries';
    case SYSTEM_SETTINGS_USERS = 'system settings users';
    case SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS = 'system settings roles and permissions';

    public function label(): string
    {
        return match ($this) {
            self::CHANNELS => 'Каналы',
            self::STATISTICS => 'Статистика',
            self::CLIENTS_AND_PROJECTS => 'Клиенты и клиенто-проекты',
            self::STATISTICS_SETTLEMENT => 'начальная статистика взаиморасчетов',
            self::CLIENTS_AND_PROJECTS_SELF => 'доступ к своим клиенто-проектам и клиентам',
            self::CLIENTS_AND_PROJECTS_ALL => 'доступ ко всем клиенто-проектам и клиентам',
            self::BUDGET_RECONCILIATION => 'Сверка бюджетов',
            self::ADVERTISING_FUNDS_MOVEMENT => 'Движение рекламных средств',
            self::ADVERTISING_FUNDS_MOVEMENT_STATUS => 'работа с колонкой "Статус"',
            self::ADVERTISING_FUNDS_MOVEMENT_INVOICE => 'работа с колонкой "Счет выставлен"',
            self::PLANNING => 'Планирование',
            self::PLANNING_APPROVAL => 'согласование в отчете "Планирование"',
            self::MEDIA_PLANNING => 'Медиапланирование',
            self::REPORTS => 'Отчеты',
            self::REPORT_TEMPLATES => 'Шаблоны отчетов',
            self::SYSTEM_SETTINGS => 'Настройки агентства',
            self::SYSTEM_SETTINGS_DICTIONARIES => 'Справочники',
            self::SYSTEM_SETTINGS_USERS => 'Пользователи и роли',
            self::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS => 'Продукты и права',
        };
    }

    public static function flatValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Используется для определения, является ли группа вторичной. У таких групп в
     * интерфейсе отображается дополнительный значок в виде треугольника.
     */
    public function isSecondary(): bool
    {
        return in_array($this, [
            self::STATISTICS_SETTLEMENT,
            self::CLIENTS_AND_PROJECTS_SELF,
            self::CLIENTS_AND_PROJECTS_ALL,
            self::ADVERTISING_FUNDS_MOVEMENT_STATUS,
            self::ADVERTISING_FUNDS_MOVEMENT_INVOICE,
            self::PLANNING_APPROVAL,
        ], true);
    }

    public static function hierarchicalValues(): array
    {
        return [
            self::CHANNELS->value => [],
            self::STATISTICS->value => [],
            self::BUDGET_RECONCILIATION->value => [],
            self::ADVERTISING_FUNDS_MOVEMENT->value => [
                self::ADVERTISING_FUNDS_MOVEMENT_STATUS,
                self::ADVERTISING_FUNDS_MOVEMENT_INVOICE,
            ],
            self::PLANNING->value => [
                self::PLANNING_APPROVAL,
            ],
            self::MEDIA_PLANNING->value => [],
            self::REPORTS->value => [],
            self::REPORT_TEMPLATES->value => [],
            self::SYSTEM_SETTINGS->value => [],
            self::SYSTEM_SETTINGS_DICTIONARIES->value => [],
            self::SYSTEM_SETTINGS_USERS->value => [],
            self::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS->value => [],
            self::CLIENTS_AND_PROJECTS->value => [
                self::STATISTICS_SETTLEMENT,
                self::CLIENTS_AND_PROJECTS_SELF,
                self::CLIENTS_AND_PROJECTS_ALL,
            ],
        ];
    }

    /**
     * Порядок групп на странице «Продукты и права»: родитель, затем дети.
     *
     * @return list<string>
     */
    public static function settingsPageGroupOrder(): array
    {
        $order = [];

        foreach (self::hierarchicalValues() as $parentValue => $children) {
            $order[] = $parentValue;

            foreach ($children as $child) {
                $order[] = $child->value;
            }
        }

        return $order;
    }

    /**
     * Группы, у которых колонка «Полный доступ» недоступна для ролей кроме администратора.
     *
     * @return list<self>
     */
    public static function fullAccessLockedForNonAdmin(): array
    {
        return [
            self::CHANNELS,
            self::STATISTICS,
            self::CLIENTS_AND_PROJECTS,
            self::CLIENTS_AND_PROJECTS_SELF,
            self::CLIENTS_AND_PROJECTS_ALL,
            self::BUDGET_RECONCILIATION,
            self::ADVERTISING_FUNDS_MOVEMENT_STATUS,
            self::ADVERTISING_FUNDS_MOVEMENT_INVOICE,
            self::PLANNING_APPROVAL,
            self::REPORTS,
            self::SYSTEM_SETTINGS,
            self::SYSTEM_SETTINGS_DICTIONARIES,
            self::SYSTEM_SETTINGS_USERS,
            self::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS,
        ];
    }

    /**
     * Группы, у которых колонка «Изменение» недоступна для ролей кроме администратора.
     *
     * @return list<self>
     */
    public static function editAccessLockedForNonAdmin(): array
    {
        return [
            self::STATISTICS,
            self::BUDGET_RECONCILIATION,
        ];
    }

    /**
     * Группы, скрытые на странице «Продукты и права» (модуль вне текущего scope).
     *
     * @return list<self>
     */
    public static function hiddenOnSettingsPage(): array
    {
        return [
            self::MEDIA_PLANNING,
        ];
    }

    public function isFullAccessLockedForNonAdmin(): bool
    {
        return in_array($this, self::fullAccessLockedForNonAdmin(), true);
    }

    public function isEditAccessLockedForNonAdmin(): bool
    {
        return in_array($this, self::editAccessLockedForNonAdmin(), true);
    }

    public function isHiddenOnSettingsPage(): bool
    {
        return in_array($this, self::hiddenOnSettingsPage(), true);
    }

    /**
     * @return list<string>
     */
    public static function fullAccessLockedGroupNames(): array
    {
        return array_map(fn (self $group) => $group->value, self::fullAccessLockedForNonAdmin());
    }

    /**
     * @return list<string>
     */
    public static function editAccessLockedGroupNames(): array
    {
        return array_map(fn (self $group) => $group->value, self::editAccessLockedForNonAdmin());
    }

    /**
     * @return list<string>
     */
    public static function hiddenOnSettingsPageGroupNames(): array
    {
        return array_map(fn (self $group) => $group->value, self::hiddenOnSettingsPage());
    }
}
