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
            $report->getPeriod()->monthYearLabel(),
        ];

        $name = implode(' ', array_filter($parts, static fn (string $part): bool => $part !== ''));

        return self::sanitize($name);
    }

    private static function sanitize(string $name): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }
}
