<?php

namespace App\Enums;

enum ChannelBulkAction: string
{
    case RefreshData = 'refresh_data';
    case RefreshBudgetRemains = 'refresh_budget_remains';

    public function label(): string
    {
        return match ($this) {
            self::RefreshData => 'Обновить данные',
            self::RefreshBudgetRemains => 'Обновить остаток бюджета',
        };
    }
}
