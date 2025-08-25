<?php

namespace App\Enums;

enum PermissionGroup: string
{
    case CHANNELS = 'channels';
    case STATISTICS = 'statistics';
    case CLIENTS_AND_PROJECTS = 'clients and projects';
    case STATISTICS_SETTLEMENT = 'statistics settlement';
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
            PermissionGroup::CHANNELS => 'Каналы',
            PermissionGroup::STATISTICS => 'Статистика',
            PermissionGroup::CLIENTS_AND_PROJECTS => 'Справочник клиентов и клиенто-проектов',
            PermissionGroup::STATISTICS_SETTLEMENT => 'Начальная статистика взаиморасчетов',
            PermissionGroup::BUDGET_RECONCILIATION => 'Сверка бюджетов',
            PermissionGroup::ADVERTISING_FUNDS_MOVEMENT => 'Движение рекламных средств',
            PermissionGroup::ADVERTISING_FUNDS_MOVEMENT_STATUS => 'работа с колонкой "Статус"',
            PermissionGroup::ADVERTISING_FUNDS_MOVEMENT_INVOICE => 'работа с колонкой "Счет выставлен"',
            PermissionGroup::PLANNING => 'Планирование',
            PermissionGroup::PLANNING_APPROVAL => 'согласование в отчете "Планирование"',
            PermissionGroup::MEDIA_PLANNING => 'Медиапланирование',
            PermissionGroup::REPORTS => 'Отчеты',
            PermissionGroup::REPORT_TEMPLATES => 'Шаблоны отчетов',
            PermissionGroup::SYSTEM_SETTINGS => 'Настройки системы'
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
