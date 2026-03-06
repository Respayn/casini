<?php

namespace Src\Infrastructure\Reports;

use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportDataProviderInterface;
use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Users\UserRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class ReportDataProvider implements ReportDataProviderInterface
{
    private readonly ProjectRepositoryInterface $projectRepository;
    private readonly ClientRepositoryInterface $clientRepository;
    private readonly UserRepositoryInterface $userRepository;

    public function __construct(
        ProjectRepositoryInterface $projectRepository,
        ClientRepositoryInterface $clientRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->projectRepository = $projectRepository;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
    }

    public function getData(int $projectId, DateTimeRange $period): ReportData
    {
        $project = $this->projectRepository->findById($projectId);
        $client = $this->clientRepository->findById($project->getClientId());
        $manager = $this->userRepository->findById($client->getManagerId());

        $currentYear = date('Y');

        /**
         * Inline
         * 
         * % топ 3
         * % топ 5
         * % топ 10
         */


        /**
         * Tables
         * 
         * Позиции сайта в Яндекс по запросу
         * Переходы из поисковых систем
         * Посещаемость из ПС
         * Переходы по поисковым фразам
         * Переходы посетителей по регионам
         */

        /** 
         * Conditions 
         * Проведенные работы
         *
         */

        /**
         * Lists
         * 
         * Проведенные работы
         */

        $data = ReportData::builder()
            ->value('current_year', $currentYear)
            ->value('project_name', $project->getName())
            ->value('project_domain', $project->getDomain())
            ->value('period_from', $period->getStart()->format('d.m.Y'))
            ->value('period_to', $period->getEnd()->format('d.m.Y'))
            ->value('manager_first_name', $manager->getFirstName())
            ->value('manager_last_name', $manager->getLastName())
            ->value('manager_email', $manager->getEmail())
            ->value('manager_phone', $manager->getPhone())
            ->table('table', [
                ['row 1-1', 'row 1-2', 'row 1-3'],
                ['row 2-1', 'row 2-2', 'row 2-3']
            ])
            ->build();
        return $data;
    }
}
