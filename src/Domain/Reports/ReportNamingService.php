<?php

namespace Src\Domain\Reports;

use Src\Domain\Projects\Project;

class ReportNamingService
{
    public function generateName(Report $report, Project $project): string
    {
        $parts = [
            'Отчет',
            $project->getType()->label(),
            trim($project->getDomain()),
            ReportPeriodBuckets::make($report->getPeriod())->reportingMonth->monthYearLabel(),
        ];

        $name = implode(' ', array_filter($parts, static fn (string $part): bool => $part !== ''));

        if ($report->getId() !== null) {
            $name .= ' '.$report->getId();
        }

        return self::sanitize($name);
    }

    /**
     * Имя файла на диске. У новой записи номера ещё нет, поэтому короткий хвост
     * только для диска, чтобы два отчёта за один месяц не затирали друг друга.
     * При скачивании имя собирается через generateName уже с номером из БД.
     */
    public function generateStorageName(Report $report, Project $project): string
    {
        $name = $this->generateName($report, $project);

        if ($report->getId() === null) {
            $name .= ' '.bin2hex(random_bytes(4));
        }

        return $name;
    }

    private static function sanitize(string $name): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }
}
