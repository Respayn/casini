<?php

namespace Src\Application\Reports\Download;

use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Reports\ReportNamingService;
use Src\Domain\Reports\ReportRepositoryInterface;

class DownloadReportFileQueryHandler
{
    private readonly ReportRepositoryInterface $reportRepository;
    private readonly ProjectRepositoryInterface $projectRepository;
    private readonly ReportNamingService $reportNamingService;

    public function __construct(
        ReportRepositoryInterface $reportRepository,
        ProjectRepositoryInterface $projectRepository,
        ReportNamingService $reportNamingService
    ) {
        $this->reportRepository = $reportRepository;
        $this->projectRepository = $projectRepository;
        $this->reportNamingService = $reportNamingService;
    }

    public function handle(DownloadReportFileQuery $query)
    {
        $reportId = $query->reportId;

        $report = $this->reportRepository->findById($reportId);
        $project = $this->projectRepository->findById($report->getProjectId());

        return new DownloadReportFileDto(
            $report->getPath(),
            $this->reportNamingService->generateName($report, $project)
        );
    }
}
