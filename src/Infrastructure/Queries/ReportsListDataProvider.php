<?php

namespace Src\Infrastructure\Queries;

use App\Models\User;
use App\Support\ClientsAndProjectsPermissions;
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
    public function getList(
        bool $showInactiveProjects,
        DateTimeRange $period,
        ?int $userId,
        ?int $projectId = null,
    ): array {
        $query = DB::table('reports')
            ->leftJoin('templates', 'templates.id', '=', 'reports.template_id')
            ->join('clients', 'clients.id', '=', 'reports.client_id')
            ->join('projects', 'projects.id', '=', 'reports.project_id')
            ->join('users as specialists', 'specialists.id', '=', 'reports.specialist_id');

        if ($projectId !== null) {
            $query = $query->where('projects.id', '=', $projectId);
        } elseif (! $showInactiveProjects) {
            $query = $query->where('projects.is_active', '=', true);
        }

        $query = $query->where('reports.created_at', '>=', $period->start->format('Y-m-d 00:00:00'))
            ->where('reports.created_at', '<=', $period->end->format('Y-m-d 23:59:59'));

        // Применяем фильтрацию по правам доступа
        $query = $this->applyAccessFilter($query, $userId);

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
                'reports.template_name as stored_template_name',
                'templates.name as live_template_name',
                'clients.id as client_id',
                'clients.name as client_name',
                'projects.id as project_id',
                'projects.name as project_name',
                'projects.project_type as project_type',
                'specialists.first_name as specialist_first_name',
                'specialists.last_name as specialist_last_name',
            ])
            ->orderBy('reports.created_at', 'desc')
            ->get();

        return $results->map(fn (object $row) => new ReportListItemDto(
            new DateTimeImmutable($row->created_at),
            $this->resolveTemplateName($row),
            $row->client_id,
            $row->client_name,
            $row->report_id,
            ProjectType::from($row->project_type)->shortLabel(),
            $row->project_id,
            $row->project_name,
            new DateTimeImmutable($row->period_start),
            new DateTimeImmutable($row->period_end),
            $row->specialist_last_name.' '.mb_substr($row->specialist_first_name, 0, 1).'.',
            ReportFormat::from($row->format)->value,
            $row->is_ready,
            $row->is_accepted,
            $row->is_sent
        ))->toArray();
    }

    private function resolveTemplateName(object $row): string
    {
        if (is_string($row->stored_template_name) && $row->stored_template_name !== '') {
            return $row->stored_template_name;
        }

        if (is_string($row->live_template_name) && $row->live_template_name !== '') {
            return $row->live_template_name;
        }

        return 'Шаблон удалён';
    }

    /**
     * Тот же контур, что у карточки проекта и скачивания: «все» или «свои».
     */
    private function applyAccessFilter(Builder $query, ?int $userId): Builder
    {
        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if (ClientsAndProjectsPermissions::userCanSeeAll($user)) {
            return $query;
        }

        if (! ClientsAndProjectsPermissions::userCanSeeSelf($user)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($userId): void {
            $scoped
                ->where('projects.specialist_id', '=', $userId)
                ->orWhere('clients.manager_id', '=', $userId);
        });
    }
}
