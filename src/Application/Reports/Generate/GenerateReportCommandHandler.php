<?php

namespace Src\Application\Reports\Generate;

use Src\Application\Reports\Generate\ReportGeneratorInterface;
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
    private readonly ReportGeneratorInterface $reportGenerator;
    private readonly ReportRepositoryInterface $reportRepository;
    private readonly ProjectRepositoryInterface $projectRepository;
    private readonly TemplateRepositoryInterface $templateRepository;
    private readonly ClientRepositoryInterface $clientRepository;
    private readonly ReportDataProviderInterface $reportDataProvider;
    private readonly ReportNamingService $reportNamingService;

    public function __construct(
        ReportGeneratorInterface $reportGenerator,
        ReportRepositoryInterface $reportRepository,
        ProjectRepositoryInterface $projectRepository,
        TemplateRepositoryInterface $templateRepository,
        ClientRepositoryInterface $clientRepository,
        ReportDataProviderInterface $reportDataProvider,
        ReportNamingService $reportNamingService
    ) {
        $this->reportGenerator = $reportGenerator;
        $this->reportRepository = $reportRepository;
        $this->projectRepository = $projectRepository;
        $this->templateRepository = $templateRepository;
        $this->clientRepository = $clientRepository;
        $this->reportDataProvider = $reportDataProvider;
        $this->reportNamingService = $reportNamingService;
    }

    public function handle(GenerateReportCommand $command): int
    {
        $template = $this->templateRepository->findById($command->templateId);
        $project = $this->projectRepository->findById($command->projectId);
        $client = $this->clientRepository->findById($project->getClientId());

        $period = new DateTimeRange($command->from, $command->to);

        $reportData = $this->reportDataProvider->getData($command->projectId, $period);
        
        $report = Report::create(
            $template->getId(),
            $project->getClientId(),
            $project->getType(),
            $project->getId(),
            $period,
            $project->getSpecialistId(),
            $client->getManagerId(),
            ReportFormat::from($command->format),
            false,
            false,
            false,
            $command->createdBy,
        );

        $reportName = $this->reportNamingService->generateName($report, $project);
        $reportPath = $this->reportGenerator->generate($template->getPath(), $reportData, $reportName);

        $report->setPath($reportPath);

        return $this->reportRepository->save($report);
    }
}
