<?php

namespace App\Services;

use App\Data\Accounting\WorkActData;
use App\Models\SaoPerformedWorkAct;
use App\Repositories\IntegrationRepository;
use App\Repositories\Interfaces\WorkActRepositoryInterface;
use App\Services\WorkActs\WorkActRelationService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;

class AccountingService
{
    public function __construct(
        private ConnectionInterface $db,
        private IntegrationRepository $integrationRepository,
        private WorkActRepositoryInterface $workActRepo,
        private WorkActRelationService $workActRelationService,
    ) {}

    /**
     * Основной метод обработки актов
     */
    public function processWorkActs(DataCollection $workActs): void
    {
        $this->db->transaction(function () use ($workActs) {
            foreach ($workActs as $workActData) {
                $this->processSingleWorkAct($workActData);
            }
        });
    }

    private function processSingleWorkAct(WorkActData $workActData): void
    {
        $projectId = $this->findMatchingProjectId($workActData);

        if ($projectId === null) {
            Log::warning('Акт 1С не сопоставлен с клиенто-проектом', [
                'act_number' => $workActData->actNumber,
                'inn' => $workActData->company->inn,
                'contract_number' => $workActData->company->contractNumber,
            ]);
        }

        $workAct = $this->workActRepo->upsertWorkAct($workActData, $projectId);
        $this->workActRepo->syncWorkActItems($workAct, $workActData->items);
    }

    private function findMatchingProjectId(WorkActData $workActData): ?int
    {
        $integrations = $this->integrationRepository->getEnabledProjectIntegrationsByCode('1c_acts');
        if ($integrations->isEmpty()) {
            return null;
        }

        $contexts = $integrations
            ->map(function ($integrationProject) {
                $settings = is_array($integrationProject->settings) ? $integrationProject->settings : [];
                $mainContractNumber = trim((string) ($settings['main_contract_number'] ?? ''));
                if ($mainContractNumber === '') {
                    return null;
                }

                $project = $integrationProject->project;
                if ($project === null) {
                    return null;
                }

                return (object) [
                    'projectId' => (int) $project->id,
                    'mainContractNumber' => $mainContractNumber,
                    'additionalAgreementNumbers' => $this->workActRelationService->parseAdditionalAgreementNumbers(
                        isset($settings['additional_agreement_number'])
                            ? (string) $settings['additional_agreement_number']
                            : null
                    ),
                    'inn' => $project->inn !== null ? (string) $project->inn : null,
                ];
            })
            ->filter()
            ->values();

        if ($contexts->isEmpty()) {
            return null;
        }

        $temporaryAct = new SaoPerformedWorkAct([
            'customer_inn' => $workActData->company->inn,
            'contract_number' => $workActData->company->contractNumber,
            'customer_additional_number' => $workActData->company->additionalNumbers,
            'price' => $workActData->total,
        ]);

        return $this->workActRelationService->findBestMatchingProjectId($temporaryAct, $contexts);
    }
}
