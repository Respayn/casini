<?php

namespace Src\Infrastructure\Persistence;

use App\Models\Report as EloquentReport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Src\Domain\Reports\Report;
use Src\Domain\Reports\ReportRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class ReportRepository implements ReportRepositoryInterface
{
    public function findById(int $id): Report
    {
        $eloquentReport = EloquentReport::with('project')->findOrFail($id);

        return $this->mapToEntity($eloquentReport);
    }

    public function save(Report $report): int
    {
        $reportId = $report->getId();
        if ($reportId === null) {
            $eloquentReport = new EloquentReport;
        } else {
            $eloquentReport = EloquentReport::query()->findOrFail($reportId);
        }
        $eloquentReport->template_id = $report->getTemplateId();
        $eloquentReport->client_id = $report->getClientId();
        $eloquentReport->project_id = $report->getProjectId();
        $eloquentReport->period_start = $report->getPeriodStart();
        $eloquentReport->period_end = $report->getPeriodEnd();
        $eloquentReport->specialist_id = $report->getSpecialistId();
        $eloquentReport->manager_id = $report->getManagerId();
        $eloquentReport->format = $report->getFormat();
        $eloquentReport->is_ready = $report->getIsReady();
        $eloquentReport->is_accepted = $report->getIsAccepted();
        $eloquentReport->is_sent = $report->getIsSent();
        $eloquentReport->created_by = $report->getCreatedBy();
        $eloquentReport->created_at = $report->getCreatedAt();
        $eloquentReport->path = $report->getPath();
        $eloquentReport->save();

        return $eloquentReport->id;
    }

    public function remove(Report $report): void
    {
        EloquentReport::query()->whereKey($report->getId())->delete();
        $this->deleteStoredFile($report->getPath());
    }

    private function deleteStoredFile(string $path): void
    {
        if ($path === '') {
            return;
        }

        if (File::exists($path)) {
            File::delete($path);

            return;
        }

        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    private function mapToEntity(EloquentReport $eloquentReport): Report
    {
        return Report::restore(
            $eloquentReport->id,
            $eloquentReport->created_at,
            $eloquentReport->template_id,
            $eloquentReport->client_id,
            $eloquentReport->project->project_type,
            $eloquentReport->project_id,
            new DateTimeRange(
                $eloquentReport->period_start,
                $eloquentReport->period_end
            ),
            $eloquentReport->specialist_id,
            $eloquentReport->manager_id,
            $eloquentReport->format,
            $eloquentReport->is_ready,
            $eloquentReport->is_accepted,
            $eloquentReport->is_sent,
            $eloquentReport->created_by,
            $eloquentReport->path
        );
    }
}
