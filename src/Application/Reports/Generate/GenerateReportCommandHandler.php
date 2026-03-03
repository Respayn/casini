<?php

namespace Src\Application\Reports\Generate;

use Src\Application\Reports\ReportGeneratorInterface;
use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Reports\Report;
use Src\Domain\Reports\ReportFormat;
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

    public function __construct(
        ReportGeneratorInterface $reportGenerator,
        ReportRepositoryInterface $reportRepository,
        ProjectRepositoryInterface $projectRepository,
        TemplateRepositoryInterface $templateRepository,
        ClientRepositoryInterface $clientRepository
    ) {
        $this->reportGenerator = $reportGenerator;
        $this->reportRepository = $reportRepository;
        $this->projectRepository = $projectRepository;
        $this->templateRepository = $templateRepository;
        $this->clientRepository = $clientRepository;
    }

    public function handle(GenerateReportCommand $command): int
    {
        // создать отчет
        // $this->reportGenerator->generate();

        $template = $this->templateRepository->findById($command->templateId);
        // TODO: check template existence

        $project = $this->projectRepository->findById($command->projectId);
        // TODO: check project existence

        $client = $this->clientRepository->findById($project->getClientId());

        $period = new DateTimeRange($command->from, $command->to);

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
            'stub'
        );

        return $this->reportRepository->save($report);
    }
}
