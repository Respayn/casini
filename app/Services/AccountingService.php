<?php

namespace App\Services;

use App\Data\Accounting\WorkActData;
use App\Exceptions\ProjectNotFoundException;
use App\Repositories\Interfaces\WorkActRepositoryInterface;
use App\Repositories\ProjectRepository;
use Illuminate\Database\ConnectionInterface;
use Log;
use Spatie\LaravelData\DataCollection;

class AccountingService
{
    public function __construct(
        private ConnectionInterface $db,
        private ProjectRepository $projectRepo,
        private WorkActRepositoryInterface $workActRepo
    ) {}

    /**
     * Основной метод обработки актов
     */
    public function processWorkActs(DataCollection $workActs): void
    {
        $this->db->transaction(function () use ($workActs) {
            foreach ($workActs as $workActData) {
                try {
                    $this->processSingleWorkAct($workActData);
                } catch (ProjectNotFoundException $e) {
                    Log::warning($e->getMessage());
                    continue; // Пропускаем акт если проект не найден
                }
            }
        });
    }

    /**
     * Обрабатывает один акт
     * @throws ProjectNotFoundException
     */
    private function processSingleWorkAct(WorkActData $workActData): void
    {
        $project = $this->projectRepo->findProjectByCompanyData($workActData->company);
        $workAct = $this->workActRepo->upsertWorkAct($workActData, $project->id);
        $this->workActRepo->syncWorkActItems($workAct, $workActData->items);
    }
}
