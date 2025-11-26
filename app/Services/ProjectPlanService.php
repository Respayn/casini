<?php

namespace App\Services;

use App\Contracts\ProjectPlanRepositoryInterface;
use App\Data\PlanningReportQueryData;

class ProjectPlanService
{
    // private ClientRepository $clientRepository;
    // private ProjectRepository $projectRepository;
    private ProjectPlanRepositoryInterface $projectPlanRepository;

    /**
     * Create a new class instance.
     */
    public function __construct(
        // ClientRepository $clientRepository,
        // ProjectRepository $projectRepository,
        ProjectPlanRepositoryInterface $projectPlanRepository
    ) {
        // $this->clientRepository = $clientRepository;
        // $this->projectRepository = $projectRepository;
        $this->projectPlanRepository = $projectPlanRepository;
    }

    public function getPlansForYear(int $year): array
    {
        return $this->projectPlanRepository->getPlansForYear($year);
    }

    public function savePlansForYear(int $year, array $plans)
    {
        $this->projectPlanRepository->savePlansForYear($year, $plans);
    }

    // /**
    //  * Получить фильтры для страницы планирования
    //  * @return PlanningReportQueryData
    //  */
    // public function getQueryData(): PlanningReportQueryData
    // {
    //     return PlanningReportQueryData::create();
    // }

    // public function getReportData(): TableReportData
    // {
    //     $clients = $this->clientRepository->all();
    //     $projects = $this->projectRepository->all();

    //     if ($projects->isEmpty()) {
    //         return $this->createEmptyDataSet();
    //     }

    //     return $this->createReportData($clients, $projects);
    // }

    // private function createEmptyDataSet(): TableReportData
    // {
    //     return new TableReportData();
    // }

    // private function createReportData(Collection $clients, Collection $projects)
    // {
    //     $reportData = new TableReportData();
    //     $group = new TableReportGroupData();

    //     $rows = new Collection();

    //     foreach ($projects as $project) {
    //         $row = new TableReportRowData();

    //         $department = match ($project->project_type) {
    //             ProjectType::CONTEXT_AD => 'Контекст',
    //             ProjectType::SEO_PROMOTION => 'SEO'
    //         };

    //         $client = $clients->firstWhere('id', $project->client_id);

    //         $row->data = new Collection([
    //             'client' => [
    //                 'name' => $client->name
    //             ],
    //             'client-project' => [
    //                 'id' => $project->id,
    //                 'name' => $project->name
    //             ],
    //             'client-project-created-at' => [
    //                 'created_at' => $project->created_at
    //             ],
    //             'client-project-id' => [
    //                 'id' => $project->id
    //             ],
    //             'department' => [
    //                 'name' => $department
    //             ],
    //             'kpi' => $project->kpi->label(),
    //             'parameter' => $this->createParameterData($project->project_type, $project->kpi),
    //             'january' => $this->createPlanData($project->project_type, $project->kpi),
    //             'february' => $this->createPlanData($project->project_type, $project->kpi),
    //             'march' => $this->createPlanData($project->project_type, $project->kpi),
    //             'april' => $this->createPlanData($project->project_type, $project->kpi),
    //             'may' => $this->createPlanData($project->project_type, $project->kpi),
    //             'june' => $this->createPlanData($project->project_type, $project->kpi),
    //             'july' => $this->createPlanData($project->project_type, $project->kpi),
    //             'august' => $this->createPlanData($project->project_type, $project->kpi),
    //             'september' => $this->createPlanData($project->project_type, $project->kpi),
    //             'october' => $this->createPlanData($project->project_type, $project->kpi),
    //             'november' => $this->createPlanData($project->project_type, $project->kpi),
    //             'december' => $this->createPlanData($project->project_type, $project->kpi),
    //         ]);

    //         $rows->add($row);
    //     }

    //     $group->rows = $rows;

    //     $reportData->groups->add($group);
    //     return $reportData;
    // }

    // private function createParameterData(ProjectType $projectType, Kpi $kpi): array
    // {
    //     return match ($projectType) {
    //         ProjectType::CONTEXT_AD => match ($kpi) {
    //             Kpi::TRAFFIC => [
    //                 ['name' => 'CPC', 'highlight' => false],
    //                 ['name' => 'Рекламный бюджет', 'highlight' => false],
    //                 ['name' => 'Объём визитов', 'highlight' => true]
    //             ],
    //             Kpi::LEADS => [
    //                 ['name' => 'CPL', 'highlight' => false],
    //                 ['name' => 'Рекламный бюджет', 'highlight' => false],
    //                 ['name' => 'Лидов', 'highlight' => true]
    //             ],
    //         },
    //         ProjectType::SEO_PROMOTION => match ($kpi) {
    //             Kpi::TRAFFIC => [
    //                 ['name' => 'Объём визитов', 'highlight' => true],
    //                 ['name' => 'Конверсии', 'highlight' => false]
    //             ],
    //             Kpi::POSITIONS => [
    //                 ['name' => '% позиций в топ 10', 'highlight' => false],
    //                 ['name' => 'Конверсии', 'highlight' => false]
    //             ]
    //         }
    //     };
    // }

    // // TODO: скорее всего сюда нужно будет передавать ID проекта или данные, которые будут получены заранее
    // // Пока просто описана структура
    // private function createPlanData(ProjectType $projectType, Kpi $kpi): array
    // {
    //     return match ($projectType) {
    //         ProjectType::CONTEXT_AD => match ($kpi) {
    //             Kpi::TRAFFIC => [
    //                 ['value' => 45, 'format' => 'currency'],
    //                 ['value' => 90000, 'format' => 'currency'],
    //                 ['value' => 1670, 'format' => null]
    //             ],
    //             Kpi::LEADS => [
    //                 ['value' => 3392, 'format' => 'currency'],
    //                 ['value' => 190000, 'format' => 'currency'],
    //                 ['value' => 56, 'format' => null]
    //             ],
    //         },
    //         ProjectType::SEO_PROMOTION => match ($kpi) {
    //             Kpi::TRAFFIC => [
    //                 ['value' => 5130, 'format' => null],
    //                 ['value' => null, 'format' => null]
    //             ],
    //             Kpi::POSITIONS => [
    //                 ['value' => 50, 'format' => null],
    //                 // ['value' => 50, 'format' => 'percent'],
    //                 ['value' => null, 'format' => null]
    //             ]
    //         }
    //     };
    // }
}
