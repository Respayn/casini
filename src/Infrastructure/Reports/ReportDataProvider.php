<?php

namespace Src\Infrastructure\Reports;

use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportDataProviderInterface;
use Src\Domain\Agencies\AgencyRepositoryInterface;
use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Domain\Leads\CallibriLeadRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Users\UserRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class ReportDataProvider implements ReportDataProviderInterface
{
    private readonly ProjectRepositoryInterface $projectRepository;
    private readonly ClientRepositoryInterface $clientRepository;
    private readonly UserRepositoryInterface $userRepository;
    private readonly CallibriLeadRepositoryInterface $callibriLeadRepository;

    public function __construct(
        ProjectRepositoryInterface $projectRepository,
        ClientRepositoryInterface $clientRepository,
        UserRepositoryInterface $userRepository,
        CallibriLeadRepositoryInterface $callibriLeadRepository
    ) {
        $this->projectRepository = $projectRepository;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->callibriLeadRepository = $callibriLeadRepository;
    }

    public function getData(int $projectId, DateTimeRange $period): ReportData
    {
        // Common
        $currentYear = date('Y');
        $builder = ReportData::builder()
            ->value('current_year', $currentYear)
            ->value('period_from', $period->start->format('d.m.Y'))
            ->value('period_to', $period->end->format('d.m.Y'));

        // Project
        $project = $this->projectRepository->findById($projectId);
        $builder->value('project_domain', $project->getDomain())
            ->value('project_name', $project->getName());

        // Manager
        $client = $this->clientRepository->findById($project->getClientId());
        $manager = $this->userRepository->findById($client->getManagerId());
        $builder->value('manager_last_name', $manager->getLastName())
            ->value('manager_first_name', $manager->getFirstName())
            ->value('manager_phone', $manager->getPhone())
            ->value('manager_email', $manager->getEmail())
            ->image('manager_photo', $manager->getImagePath() ?? '');

        // Agency
        $builder->value('agency_address', '')
            ->value('agency_domain', '')
            ->image('agency_image', '')
            ->value('agency_email', '')
            ->value('agency_phone', '');

        // Callibri
        $callibriLeads = $this->callibriLeadRepository->findByProjectId($projectId, $period);
        $callibriTableHeaders = [
            ['Дата', 'Класс', 'Тип обращения', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']
        ];
        $callibriTableRows = array_map(fn($lead) => [
            $lead->getDate()->format('d.m.Y'),
            '',
            '',
            $lead->getUtmSource(),
            $lead->getUtmMedium(),
            $lead->getUtmCampaign(),
            $lead->getUtmContent(),
            $lead->getUtmTerm()
        ], $callibriLeads);
        $callibriTable = array_merge($callibriTableHeaders, $callibriTableRows);
        $builder->table('callibri.table', $callibriTable);

        $builder->value('yandex_search.top_3', '')
            ->value('yandex_search.top_5', '')
            ->value('yandex_search.top_10', '')

            ->table('yandex_search.table', [])

            ->table('yandex_direct.table', [])

            ->table('yandex_metrika.table.achievement_of_goals_from_the_report_search_engines', [])
            ->table('yandex_metrika.table.achievement_of_goals_from_the_report_search_engines.yandex', [])
            ->table('yandex_metrika.table.achievement_of_goals_from_the_report_search_engines.google', [])

            ->table('yandex_metrika.table.achievement_of_goals_from_the_report_utm_tags', [])

            ->table('yandex_metrika.table.achievement_of_goals_from_the_report_conversions', [])

            ->table('yandex_metrika.table.transitions_from_report_search_systems', [])

            ->table('yandex_metrika.table.transitions_from_report_geography', [])

            ->table('yandex_metrika.table.transitions_from_report_search_queries', [])

            ->list('megaplan.list', [])

            ->table('yandex_direct.table.matching_by_utm_campaign', []);

        return $builder->build();
    }
}
