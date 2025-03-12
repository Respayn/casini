<?php

namespace App\Livewire\SystemSettings\ClientAndProjects;

use App\Data\BonusData;
use App\Data\IntervalData;
use App\Data\ProjectData;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectBonusGuaranteeForm;
use App\Services\ClientService;
use App\Services\DepartmentService;
use App\Services\ProjectService;
use App\Services\PromotionRegionService;
use App\Services\PromotionTopicService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ClientProjectFormModel extends Component
{
    public CreateClientProjectForm $clientProjectForm;
    public ProjectBonusGuaranteeForm $bonusGuaranteeForm;

    private DepartmentService $departmentService;
    private ClientService $clientService;
    private PromotionRegionService $promotionRegionService;
    private PromotionTopicService $promotionTopicService;

    public Collection $departments;
    public Collection $clients;
    public Collection $promotionRegions;
    public Collection $promotionTopics;

    public function boot(
        DepartmentService $departmentService,
        ClientService $clientService,
        PromotionRegionService $promotionRegionService,
        PromotionTopicService $promotionTopicService,
    )
    {
        $this->departmentService = $departmentService;
        $this->clientService = $clientService;
        $this->promotionRegionService = $promotionRegionService;
        $this->promotionTopicService = $promotionTopicService;
    }

    public function mount($projectId = null)
    {
        $this->departments = $this->departmentService->getDepartments();
        $this->clients = $this->clientService->getClients();
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();

        if ($projectId) {
            // TODO: Определить получение данных
        } else {
            $this->clientProjectForm->isActive = true;
            $this->clientProjectForm->promotionRegions[] = null;
            $this->clientProjectForm->promotionTopics[] = null;
        }
    }

    public function render()
    {
        return view('livewire.system-settings.client-and-projects.client-project-form');
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
                department_id: $this->clientProjectForm->department,
                project_type: $this->clientProjectForm->projectType ?? null,
                kpi: $this->clientProjectForm->kpi,
                isActive: $this->clientProjectForm->isActive ?? true,
                isInternal: $this->clientProjectForm->isInternal ?? false,
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
            $project = $projectService->createOrUpdateProject($projectData);

            // Синхронизация помощников
            if (!empty($this->clientProjectForm->assistants)) {
                $assistantIds = collect($this->clientProjectForm->assistants)->filter()->all();
                $projectService->syncAssistants($project, $assistantIds);
            } else {
                $projectService->syncAssistants($project, []);
            }

            // Синхронизация регионов продвижения
            if (!empty($this->clientProjectForm->promotionRegions)) {
                $promotionRegionIds = collect($this->clientProjectForm->promotionRegions)->filter()->all();
                $projectService->syncPromotionRegions($project, $promotionRegionIds);
            } else {
                $projectService->syncPromotionRegions($project, []);
            }

            // Синхронизация тематик продвижения
            if (!empty($this->clientProjectForm->promotionTopics)) {
                $promotionTopicIds = collect($this->clientProjectForm->promotionTopics)->filter()->all();
                $projectService->syncPromotionTopics($project, $promotionTopicIds);
            } else {
                $projectService->syncPromotionTopics($project, []);
            }

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
