<?php

namespace App\Enums;

enum ChannelBulkAction: string
{
    case RefreshSpendings = 'refresh_spendings';
    case RefreshBudgetRemains = 'refresh_budget_remains';

    public function label(): string
    {
        return match ($this) {
            self::RefreshSpendings => 'Обновить расходы',
            self::RefreshBudgetRemains => 'Обновить остаток бюджета',
        };
    }
}
