<?php

namespace Src\Domain\Reports;

enum ReportDateSort: string
{
    case NewestFirst = 'newest_first';
    case OldestFirst = 'oldest_first';

    public function label(): string
    {
        return match ($this) {
            self::NewestFirst => 'От нового к старому',
            self::OldestFirst => 'От старого к новому',
        };
    }
}
