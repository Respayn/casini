<?php

use App\Data\BonusData;
use App\Data\Integrations\IntegrationData;
use App\Data\IntervalData;
use App\Data\ProjectData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Data\ProjectUtmMappingData;
use App\Enums\IntegrationCategory;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;
use App\Factories\IntegrationSettingsFactory;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectBonusGuaranteeForm;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectUtmMappingForm;
use App\Services\ClientService;
use App\Services\IntegrationService;
use App\Services\ProjectService;
use App\Services\PromotionRegionService;
use App\Services\PromotionTopicService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::system-settings')]
#[Title('Создание проекта')]
class extends Component
{
    public CreateClientProjectForm $clientProjectForm;
    public ProjectBonusGuaranteeForm $bonusGuaranteeForm;
    public ProjectUtmMappingForm $utmMappingForm;

    private ClientService $clientService;
    private ProjectService $projectService;
    private PromotionRegionService $promotionRegionService;
    private PromotionTopicService $promotionTopicService;
    private IntegrationService $integrationService;
    private UserService $userService;

    public Collection $clients;
    public Collection $promotionRegions;
    public Collection $promotionTopics;

    public ProjectIntegrationData $selectedIntegration;

    public Collection $integrationSettings;

    public function boot(
        ClientService $clientService,
        ProjectService $projectService,
        PromotionRegionService $promotionRegionService,
        PromotionTopicService $promotionTopicService,
        IntegrationService $integrationService,
        UserService $userService
    )
    {
        $this->clientService = $clientService;
        $this->projectService = $projectService;
        $this->promotionRegionService = $promotionRegionService;
        $this->promotionTopicService = $promotionTopicService;
        $this->integrationService = $integrationService;
        $this->userService = $userService;
    }

    public function mount(Request $request, $projectId = null)
    {
        $this->clients = $this->clientService->getClients();
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();
        $this->integrationSettings = collect();

        if ($projectId) {
            // Получение данных
            $project = $this->projectService->getProjectDataById($projectId);
            $client = $this->clientService->getById($project->client_id);
            
            $this->clientProjectForm->from($project);
            $this->clientProjectForm->manager = $client->manager_id;
            $this->bonusGuaranteeForm->from($project->bonusCondition);
            $this->utmMappingForm->from($project->utmMappings->toArray());
            $this->integrationSettings = $this->integrationService->getIntegrationSettingsForProject($projectId);
        } else {
            $this->clientProjectForm->isActive = true;
        }

        if ($request->input('state')) {
            $state = json_decode(Crypt::decryptString(base64_decode($request->input('state'))), true);
            $cachedData = Cache::pull('integration_data_' . $state['cache_data_id']);

            if ($cachedData) {
                /** @var Layout $layout */
                $layout = $cachedData[0];
                $this->fill((array)$layout->getComponent());
            }

            foreach ($state['integrations'] as $setting) {
                $integrationData = new ProjectIntegrationData();
                $integrationData->integration = IntegrationData::from($setting['integration']);
                $integrationData->settings = $setting['settings'];
                $integrationData->isEnabled = $setting['isEnabled'];
                $this->integrationSettings[$integrationData->integration->id] = $integrationData;
            }
        }

        if (empty($this->clientProjectForm->promotionRegions)) {
            $this->clientProjectForm->promotionRegions[] = null;
        }

        if (empty($this->clientProjectForm->promotionTopics)) {
            $this->clientProjectForm->promotionTopics[] = null;
        }
    }

    #[Computed]
    public function integrations(): Collection
    {
        return $this->integrationService->getIntegrations();
    }

    #[Computed]
    public function moneyIntegrations(): Collection
    {
        return $this->integrations()
            ->filter(fn($integration) => $integration->category === IntegrationCategory::MONEY);
    }

    #[Computed]
    public function analyticsIntegrations(): Collection
    {
        return $this->integrations()
            ->filter(fn($integration) => $integration->category === IntegrationCategory::ANALYTICS);
    }

    #[Computed]
    public function toolsIntegrations(): Collection
    {
        return $this->integrations()
            ->filter(fn($integration) => $integration->category === IntegrationCategory::TOOLS);
    }

    #[Computed]
    public function configuredMoneyIntegrations(): Collection
    {
        $moneyIntegrationIds = $this->moneyIntegrations()->pluck('id');
        return $this->integrationSettings->filter(fn ($setting, $integrationId) => $moneyIntegrationIds->contains($integrationId));
    }

    #[Computed]
    public function configuredAnalyticsIntegrations(): Collection
    {
        $analyticsIntegrationIds = $this->analyticsIntegrations()->pluck('id');
        return $this->integrationSettings->filter(fn ($setting, $integrationId) => $analyticsIntegrationIds->contains($integrationId));
    }

    #[Computed]
    public function configuredToolsIntegrations(): Collection
    {
        $toolsIntegrationIds = $this->toolsIntegrations()->pluck('id');
        return $this->integrationSettings->filter(fn ($setting, $integrationId) => $toolsIntegrationIds->contains($integrationId));
    }

    #[Computed]
    public function managers()
    {
        return $this->userService->getManagers();
    }

    #[Computed]
    public function specialists()
    {
        return $this->userService->getSpecialists();
    }

    public function selectIntegration(string $code)
    {
        $integration = $this->integrations()->firstWhere('code', $code);

        if ($this->integrationSettings->has($integration->id)) {
            $this->selectedIntegration = $this->integrationSettings->get($integration->id);
        } else {
            $integrationSettingsFactory = new IntegrationSettingsFactory();
            $selectedIntegration = new ProjectIntegrationData();
            $selectedIntegration->integration = IntegrationData::from($integration);
            $selectedIntegration->isEnabled = false;
            $selectedIntegration->settings = $integrationSettingsFactory->create($code)->toArray();
            $this->selectedIntegration = $selectedIntegration;
        }

        $this->dispatch('modal-show', name: 'integration-settings-modal');
    }

    public function setIntegrationSettings(int $integrationId, array $settings)
    {
        $integration = $this->integrations()->firstWhere('id', $integrationId);

        $projectIntegrationData = new ProjectIntegrationData();
        $projectIntegrationData->integration = IntegrationData::from($integration);
        $settingsCollection = collect($settings);
        $projectIntegrationData->isEnabled = $settingsCollection->pull('is_enabled', false);
        $projectIntegrationData->settings = $settingsCollection->toArray();

        $this->integrationSettings[$integrationId] = $projectIntegrationData;
    }

    public function updatedIntegrationSettings($value)
    {
        if ($value && empty($this->integrationSettings[16]->settings)) {
            $this->connectYandexDirect();
        }
    }

    public function connectYandexDirect()
    {
        $this->validateIntegrationSelection();

        // Сохраняем необходимые данные в кэш
        Cache::put('integration_data_' . $this->getId(),
            $this->getAttributes()->toArray(),
            now()->addMinutes(15)
        );

        return redirect()->route('yandex_direct.oauth.redirect', [
            'project_id' => $this->clientProjectForm->id,
            'cache_data_id' => $this->getId(),
        ]);
    }

    private function validateIntegrationSelection(): void
    {
        if (!$this->selectedIntegration->integration) {
            $integration = $this->integrations()
                ->firstWhere('code', 'yandex_direct');

            $this->selectedIntegration->integration = IntegrationData::from($integration);
        }
    }

    public function removeIntegration(int $integrationId)
    {
        $this->integrationSettings->forget($integrationId);
    }

    public function setIntegrationEnabled(int $integrationId, bool $isEnabled)
    {
        $this->integrationSettings[$integrationId]->isEnabled = $isEnabled;
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

    public function addMapping()
    {
        $this->utmMappingForm->addMapping(); // Внутренний метод формы
    }

    public function removeMapping(int $index)
    {
        $this->utmMappingForm->removeMapping($index); // Внутренний метод формы
    }

    public function save()
    {
        // Валидация обязательных форм
        $this->clientProjectForm->validate();
        $this->bonusGuaranteeForm->validate();

        DB::beginTransaction();

        try {
            // Подготовка данных для проекта
            $projectData = new ProjectData(
                id: $this->clientProjectForm->id ?? null,
                name: $this->clientProjectForm->name,
                domain: $this->clientProjectForm->domain ?? null,
                client_id: $this->clientProjectForm->client,
                specialist_id: $this->clientProjectForm->specialist ?? null,
                project_type: $this->clientProjectForm->projectType ? ProjectType::from($this->clientProjectForm->projectType) : null,
                kpi: Kpi::from($this->clientProjectForm->kpi),
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
            $project = $this->projectService->updateOrCreateProject($projectData);

            // Подготовка данных для бонусных настроек
            $intervals = array_map(function ($intervalData) {
                $intervalData = new IntervalData(
                    from_percentage: (float)$intervalData['fromPercentage'],
                    to_percentage: (float)$intervalData['toPercentage'],
                    bonus_amount: isset($intervalData['bonusAmount']) ? (float)$intervalData['bonusAmount'] : null,
                    bonus_percentage: isset($intervalData['bonusPercentage']) ? (float)$intervalData['bonusPercentage'] : null,
                );
                return $intervalData;
            }, $this->bonusGuaranteeForm->intervals);

            $bonusData = new BonusData(
                bonuses_enabled: $this->bonusGuaranteeForm->bonusesEnabled,
                calculate_in_percentage: $this->bonusGuaranteeForm->calculateInPercentage,
                client_payment: $this->bonusGuaranteeForm->clientPayment,
                start_month: $this->bonusGuaranteeForm->startMonth,
                intervals: $intervals,
            );

            // Сохраняем бонусные настройки через сервис
            $this->projectService->saveBonusSettings($project, $bonusData);

            $utmMappingsData = [];

            if ($this->clientProjectForm->projectType === ProjectType::CONTEXT_AD->value) {
                // Валидация формы UTM-мэппингов
                $this->utmMappingForm->validate();

                // Подготовка данных для UTM-мэппингов с указанием project_id
                $utmMappingsData = array_map(function ($utmMapping) use ($project) {
                    $projectUtmMappingData = new ProjectUtmMappingData(
                        id: $utmMapping['id'],
                        project_id: $project->id,
                        utm_type: $utmMapping['utmType'],
                        utm_value: $utmMapping['utmValue'],
                        replacement_value: $utmMapping['replacementValue'],
                    );
                    return $projectUtmMappingData;
                }, $this->utmMappingForm->utmMappings ?? []);
            }

            // Сохраняем UTM-мэппинги через сервис
            $this->projectService->saveProjectUtmMapping($utmMappingsData, $project->id);

            $this->integrationService->updateIntegrationsSettings($project->id, $this->integrationSettings);

            DB::commit();

            // Перенаправление или другие действия
            return redirect()->route('system-settings.clients-and-projects');
        } catch (\Exception $e) {
            DB::rollBack();

            // Обработка исключения, можно добавить сообщение об ошибке
            throw $e;
        }
    }
};