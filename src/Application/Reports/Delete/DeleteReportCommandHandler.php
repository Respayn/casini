<?php

namespace Src\Application\Reports\Delete;

use Src\Domain\Reports\ReportRepositoryInterface;

class DeleteReportCommandHandler
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
    ) {}

    public function handle(DeleteReportCommand $command): void
    {
        $report = $this->reportRepository->findById($command->reportId);

        $this->reportRepository->remove($report);
    }
}
