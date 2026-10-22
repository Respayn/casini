<?php

namespace Src\Domain\ValueObjects;

use DateTimeImmutable;

class DateTimeRange
{
    public function __construct(
        public readonly ?DateTimeImmutable $start,
        public readonly ?DateTimeImmutable $end
    ) {}

    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Отчетный месяц текстом: «сентябрь 2026», при диапазоне месяцев «сентябрь 2026 - октябрь 2026».
     */
    public function monthYearLabel(): string
    {
        if ($this->start === null) {
            return '';
        }

        $startLabel = $this->formatMonthYear($this->start);

        if ($this->end === null || $this->start->format('Y-m') === $this->end->format('Y-m')) {
            return $startLabel;
        }

        return $startLabel.' - '.$this->formatMonthYear($this->end);
    }

    private function formatMonthYear(DateTimeImmutable $date): string
    {
        $months = [
            1 => 'январь',
            2 => 'февраль',
            3 => 'март',
            4 => 'апрель',
            5 => 'май',
            6 => 'июнь',
            7 => 'июль',
            8 => 'август',
            9 => 'сентябрь',
            10 => 'октябрь',
            11 => 'ноябрь',
            12 => 'декабрь',
        ];

        return $months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
