<?php

namespace Src\Infrastructure\Queries;

use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Src\Application\Reports\GetList\ReportListItemDto;
use Src\Application\Reports\GetList\ReportsListDataProviderInterface;
use Src\Domain\Reports\ReportFormat;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\ValueObjects\ProjectType;

class ReportsListDataProvider implements ReportsListDataProviderInterface
{
    public function getList(bool $showInactiveProjects, DateTimeRange $period): array
    {
        $query = DB::table('reports')
            ->join('templates', 'templates.id', '=', 'reports.template_id')
            ->join('clients', 'clients.id', '=', 'reports.client_id')
            ->join('projects', 'projects.id', '=', 'reports.project_id')
            ->join('users as specialists', 'specialists.id', '=', 'reports.specialist_id');

        if (!$showInactiveProjects) {
            $query = $query->where('projects.is_active', '=', true);
        }

        $query = $query->where(function (Builder $q) use ($period) {
            $q->where(function (Builder $sub) use ($period) {
                $sub->where('reports.period_start', '>=', $period->getStart()->format('Y-m-d'))
                    ->where('reports.period_start', '<=', $period->getEnd()->format('Y-m-d'));
            })->orWhere(function (Builder $sub) use ($period) {
                $sub->where('reports.period_end', '>=', $period->getStart()->format('Y-m-d'))
                    ->where('reports.period_end', '<=', $period->getEnd()->format('Y-m-d'));
            });
        });

        $results = $query
            ->select([
                'reports.id as report_id',
                'reports.created_at',
                'reports.period_start',
                'reports.period_end',
                'reports.format',
                'reports.is_ready',
                'reports.is_accepted',
                'reports.is_sent',
                'templates.name as template_name',
                'clients.id as client_id',
                'clients.name as client_name',
                'projects.id as project_id',
                'projects.name as project_name',
                'projects.project_type as project_type',
                'specialists.first_name as specialist_first_name',
                'specialists.last_name as specialist_last_name'
            ])
            ->orderBy('reports.created_at', 'desc')
            ->get();

        return $results->map(fn(object $row) => new ReportListItemDto(
            new DateTimeImmutable($row->created_at),
            $row->template_name,
            $row->client_id,
            $row->client_name,
            $row->report_id,
            ProjectType::from($row->project_type)->shortLabel(),
            $row->project_id,
            $row->project_name,
            new DateTimeImmutable($row->period_start),
            new DateTimeImmutable($row->period_end),
            $row->specialist_last_name . ' ' . mb_substr($row->specialist_first_name, 0, 1) . '.',
            ReportFormat::from($row->format)->value,
            $row->is_ready,
            $row->is_accepted,
            $row->is_sent
        ))->toArray();
    }
}
