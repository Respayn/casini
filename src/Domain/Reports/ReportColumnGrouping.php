<?php

namespace Src\Domain\Reports;

enum ReportColumnGrouping: string
{
    case Months = 'months';
    case Quarters = 'quarters';
    case Years = 'years';

    public function label(): string
    {
        return match ($this) {
            self::Months => 'по месяцам',
            self::Quarters => 'по кварталам',
            self::Years => 'по годам',
        };
    }
}
