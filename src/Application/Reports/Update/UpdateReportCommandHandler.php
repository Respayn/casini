<?php

namespace Src\Application\Reports\Update;

use Src\Domain\Reports\ReportRepositoryInterface;

class UpdateReportCommandHandler
{
    private readonly ReportRepositoryInterface $reportRepository;

    public function __construct(ReportRepositoryInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    public function handle(UpdateReportCommand $command): void
    {
        $report = $this->reportRepository->findById($command->reportId);

        if ($command->isReady !== null) $report->setIsReady($command->isReady);
        if ($command->isAccepted !== null) $report->setIsAccepted($command->isAccepted);
        if ($command->isSent !== null) $report->setIsSent($command->isSent);

        $this->reportRepository->save($report);
    }
}