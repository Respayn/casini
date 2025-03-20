<?php

namespace App\Livewire\SystemSettings\ClientAndProjects;

use App\Data\BonusData;
use App\Data\IntervalData;
use App\Data\ProjectData;
use App\Enums\IntegrationCategory;
use App\Enums\ProjectType;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectBonusGuaranteeForm;
use App\Services\ClientService;
use App\Services\IntegrationService;
use App\Services\ProjectService;
use App\Services\PromotionRegionService;
use App\Services\PromotionTopicService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

// TODO: Сделать получение данных и сохранение через сервисный слой
#[Layout('components.layouts.system-settings')]
class ClientProjectFormModel extends Component
{
    public CreateClientProjectForm $clientProjectForm;
    public ProjectBonusGuaranteeForm $bonusGuaranteeForm;

    private ClientService $clientService;
    private ProjectService $projectService;
    private PromotionRegionService $promotionRegionService;
    private PromotionTopicService $promotionTopicService;
    private IntegrationService $integrationService;

    public Collection $clients;
    public Collection $promotionRegions;
    public Collection $promotionTopics;

    public string $selectedIntegrationCode;

    public function boot(
        ClientService $clientService,
        ProjectService $projectService,
        PromotionRegionService $promotionRegionService,
        PromotionTopicService $promotionTopicService,
        IntegrationService $integrationService,
    )
    {
        $this->clientService = $clientService;
        $this->projectService = $projectService;
        $this->promotionRegionService = $promotionRegionService;
        $this->promotionTopicService = $promotionTopicService;
        $this->integrationService = $integrationService;
    }

    public function mount($projectId = null)
    {
        $this->clients = $this->clientService->getClients();
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();

        if ($projectId) {
            // Получение данных
            $project = $this->projectService->getProjectDataById($projectId);
            $this->clientProjectForm->from($project);
            $this->bonusGuaranteeForm->from($project->bonusCondition);
        } else {
            $this->clientProjectForm->isActive = true;
        }

        if (empty($this->clientProjectForm->promotionRegions)) {
            $this->clientProjectForm->promotionRegions[] = null;
        }

        if (empty($this->clientProjectForm->promotionTopics)) {
            $this->clientProjectForm->promotionTopics[] = null;
        }
    }

    #[Computed(cache: true)]
    public function integrations(): Collection
    {
        return $this->integrationService->getIntegrations();
    }

    #[Computed]
    public function moneyIntegrations(): Collection
    {
        return $this->integrations()->filter(fn($integration) => $integration->category === IntegrationCategory::MONEY);
    }

    #[Computed]
    public function analyticsIntegrations(): Collection
    {
        return $this->integrations()->filter(fn($integration) => $integration->category === IntegrationCategory::ANALYTICS);
    }

    #[Computed]
    public function toolsIntegrations(): Collection
    {
        return $this->integrations()->filter(fn($integration) => $integration->category === IntegrationCategory::TOOLS);
    }

    public function render()
    {
        return view('livewire.system-settings.clients-and-projects.client-project-form');
    }

    public function selectIntegrationCode(string $code)
    {
        $this->selectedIntegrationCode = $code;
        $this->dispatch('modal-show', name: 'integration-settings-modal');
    }

    public function addRegion()
    {
        $this->clientProjectForm->promotionRegions[] = null;
    }

    public function removeRegion($index)
    {
        unset($this->clientProjectForm->promotionRegions[$index]);
        $this->clientProjectForm->promotionRegions = array_values($this->clientProjectForm->promotionRegions);
    }

    public function addTopic()
    {
        $this->clientProjectForm->promotionTopics[] = null;
    }

    public function removeTopic($index)
    {
        unset($this->clientProjectForm->promotionTopics[$index]);
        $this->clientProjectForm->promotionTopics = array_values($this->clientProjectForm->promotionTopics);
    }

    public function addInterval()
    {
        $this->bonusGuaranteeForm->intervals[] = [];
    }

    public function removeInterval($index)
    {
        unset($this->bonusGuaranteeForm->intervals[$index]);

    }

    public function save()
    {
        $this->clientProjectForm->validate();
        $this->bonusGuaranteeForm->validate();

        DB::beginTransaction();

        try {
            // Инициализируем сервис
            $projectService = new ProjectService();

            // Подготовка данных для проекта
            $projectData = new ProjectData(
                id: $this->clientProjectForm->id ?? null,
                name: $this->clientProjectForm->name,
                domain: $this->clientProjectForm->domain ?? null,
                client_id: $this->clientProjectForm->client,
                specialist_id: $this->clientProjectForm->specialist ?? null,
                project_type: $this->clientProjectForm->projectType ? ProjectType::from($this->clientProjectForm->projectType) : null,
                kpi: $this->clientProjectForm->kpi,
                is_active: $this->clientProjectForm->isActive ?? true,
                is_internal: $this->clientProjectForm->isInternal ?? false,
                traffic_attribution: $this->clientProjectForm->trafficAttribution ?? null,
                metrika_counter: $this->clientProjectForm->metrikaCounter ?? null,
                metrika_targets: $this->clientProjectForm->metrikaTargets ?? null,
                google_ads_client_id: $this->clientProjectForm->googleAdsClientId ?? null,
                contract_number: $this->clientProjectForm->contractNumber ?? null,
                additional_contract_number: $this->clientProjectForm->additionalContractNumber ?? null,
                recommendation_url: $this->clientProjectForm->recommendationUrl ?? null,
                legal_entity: $this->clientProjectForm->legalEntity ?? null,
                inn: $this->clientProjectForm->inn ?? null,
            );

            // Сохраняем проект через сервис
            $project = $projectService->updateOrCreateProject($projectData);

            // Подготовка данных для бонусных настроек
            $intervals = array_map(function ($intervalData) {
                return new IntervalData(
                    from_percentage: (float)$intervalData['fromPercentage'],
                    to_percentage: (float)$intervalData['toPercentage'],
                    bonus_amount: isset($intervalData['bonusAmount']) ? (float)$intervalData['bonusAmount'] : null,
                    bonus_percentage: isset($intervalData['bonusPercentage']) ? (float)$intervalData['bonusPercentage'] : null,
                );
            }, $this->bonusGuaranteeForm->intervals);

            // Подготовка данных для бонусных настроек
            $bonusData = new BonusData(
                bonuses_enabled: $this->bonusGuaranteeForm->bonusesEnabled,
                calculate_in_percentage: $this->bonusGuaranteeForm->calculateInPercentage,
                client_payment: $this->bonusGuaranteeForm->clientPayment,
                start_month: $this->bonusGuaranteeForm->startMonth,
                intervals: $intervals,
            );

            // Сохраняем бонусные настройки через сервис
            $projectService->saveBonusSettings($project, $bonusData);

            DB::commit();

            // Перенаправление или другие действия
            return redirect()->route('system-settings.clients-and-projects');
        } catch (\Exception $e) {
            DB::rollBack();

            // Обработка исключения, можно добавить сообщение об ошибке
            throw $e;
        }
    }
}
