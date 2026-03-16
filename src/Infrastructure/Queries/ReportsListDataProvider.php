<?php

namespace Src\Infrastructure\Queries;

use App\Models\User;
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
    public function getList(bool $showInactiveProjects, DateTimeRange $period, ?int $userId): array
    {
        $query = DB::table('reports')
            ->join('templates', 'templates.id', '=', 'reports.template_id')
            ->join('clients', 'clients.id', '=', 'reports.client_id')
            ->join('projects', 'projects.id', '=', 'reports.project_id')
            ->join('users as specialists', 'specialists.id', '=', 'reports.specialist_id');

        if (!$showInactiveProjects) {
            $query = $query->where('projects.is_active', '=', true);
        }

        $query = $query->where('reports.created_at', '>=', $period->start->format('Y-m-d'))
            ->where('reports.created_at', '<=', $period->end->format('Y-m-d'));

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

    /**
     * Применяет фильтрацию по правам доступа к запросу.
     */
    private function applyAccessFilter(Builder $query, ?int $userId): Builder
    {
        // Если userId не передан, возвращаем все отчёты (для совместимости)
        if ($userId === null) {
            return $query;
        }

        $user = User::with('roles')->find($userId);

        if (!$user) {
            return $query->whereRaw('1 = 0'); // Пользователь не найден - нет доступа
        }

        $roleNames = $user->roles->pluck('name')->toArray();

        // ADMIN и руководитель отдела менеджеров видят все отчёты
        if (in_array('admin', $roleNames, true) || in_array('rucovotdelmanager', $roleNames, true)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($userId, $roleNames) {
            // Всегда: пользователь видит отчёты, которые сам создал
            $q->where('reports.created_by', '=', $userId);

            // Руководитель отдела SEO видит отчёты по SEO-проектам
            if (in_array('rucovotdelseo', $roleNames, true)) {
                $q->orWhere('projects.project_type', '=', ProjectType::SEO_PROMOTION->value);
            }

            // Руководитель отдела КР видит отчёты по контекстной рекламе
            if (in_array('rucovotdelkp', $roleNames, true)) {
                $q->orWhere('projects.project_type', '=', ProjectType::CONTEXT_AD->value);
            }

            // Менеджер видит отчёты, где он назначен менеджером
            if (in_array('manager', $roleNames, true)) {
                $q->orWhere('reports.manager_id', '=', $userId);
            }

            // Специалист видит отчёты, где он назначен специалистом
            if (in_array('seo', $roleNames, true) || in_array('kr', $roleNames, true)) {
                $q->orWhere('reports.specialist_id', '=', $userId);
            }
        });
    }
}
