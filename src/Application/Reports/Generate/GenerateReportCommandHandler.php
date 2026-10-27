<?php

namespace Src\Application\Reports\Generate;

use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Reports\Report;
use Src\Domain\Reports\ReportFormat;
use Src\Domain\Reports\ReportNamingService;
use Src\Domain\Reports\ReportRepositoryInterface;
use Src\Domain\Templates\TemplateRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class GenerateReportCommandHandler
{
    public function __construct(
        private readonly ReportGeneratorInterface $reportGenerator,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly ReportDataProviderInterface $reportDataProvider,
        private readonly ReportNamingService $reportNamingService,
    ) {}

    public function handle(GenerateReportCommand $command): GenerateReportResult
    {
        $template = $this->templateRepository->findById($command->templateId);
        $project = $this->projectRepository->findById($command->projectId);
        $client = $this->clientRepository->findById($project->getClientId());

        $period = new DateTimeRange($command->from, $command->to);

        $reportData = $this->reportDataProvider->getData($command->projectId, $period);
        $format = ReportFormat::from($command->format);

        if ($command->reportId !== null) {
            $report = $this->reportRepository->findById($command->reportId);
            $report->replace(
                $template->getId(),
                $template->getName(),
                $project->getClientId(),
                $project->getType(),
                $project->getId(),
                $period,
                $project->getSpecialistId(),
                $client->getManagerId(),
                $format,
            );
        } else {
            $report = Report::create(
                $template->getId(),
                $template->getName(),
                $project->getClientId(),
                $project->getType(),
                $project->getId(),
                $period,
                $project->getSpecialistId(),
                $client->getManagerId(),
                $format,
                false,
                false,
                false,
                $command->createdBy,
            );
        }

        $reportName = $this->reportNamingService->generateName($report, $project);
        $generated = $this->reportGenerator->generate($template->getPath(), $reportData, $reportName);

        $report->setPath($generated->path);

        $reportId = $this->reportRepository->save($report);

        return new GenerateReportResult($reportId, $generated->warnings);
    }
}
