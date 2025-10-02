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

    public function label(): string
    {
        return match ($this) {
            self::CHANNELS => 'Каналы',
            self::STATISTICS => 'Статистика',
            self::CLIENTS_AND_PROJECTS => 'Справочник клиентов и клиенто-проектов',
            self::STATISTICS_SETTLEMENT => 'Начальная статистика взаиморасчетов',
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
            self::SYSTEM_SETTINGS => 'Настройки системы'
        };
    }

    public static function flatValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Используется для определения, является ли группа вторичной. У таких групп в
     * интерфейсе отображается дополнительный значок в виде треугольника.
     * @return bool
     */
    public function isSecondary(): bool
    {
        return in_array($this, [
            self::STATISTICS_SETTLEMENT,
            self::CLIENTS_AND_PROJECTS_SELF,
            self::CLIENTS_AND_PROJECTS_ALL,
            self::ADVERTISING_FUNDS_MOVEMENT_STATUS,
            self::ADVERTISING_FUNDS_MOVEMENT_INVOICE,
            self::PLANNING_APPROVAL
        ]);
    }

    public static function hierarchicalValues(): array
    {
        return [
            self::CHANNELS => [],
            self::STATISTICS => [],
            self::CLIENTS_AND_PROJECTS => [
                self::STATISTICS_SETTLEMENT,
                self::CLIENTS_AND_PROJECTS_SELF,
                self::CLIENTS_AND_PROJECTS_ALL
            ],
            self::BUDGET_RECONCILIATION => [],
            self::ADVERTISING_FUNDS_MOVEMENT => [
                self::ADVERTISING_FUNDS_MOVEMENT_STATUS,
                self::ADVERTISING_FUNDS_MOVEMENT_INVOICE
            ],
            self::PLANNING => [
                self::PLANNING_APPROVAL
            ],
            self::MEDIA_PLANNING => [],
            self::REPORTS => [],
            self::REPORT_TEMPLATES => [],
            self::SYSTEM_SETTINGS => []
        ];
    }
}
