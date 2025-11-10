<?php

namespace App\Domain\Statistics\Enums;

enum StatisticsReportDetailLevel: string
{
    case BY_DAY = 'by_day';
    case BY_WEEK = 'by_week';
    case BY_MONTH = 'by_month';
}
