<?php

use App\Data\BonusData;
use App\Data\Integrations\IntegrationData;
use App\Data\IntervalData;
use App\Data\ProjectData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Data\ProjectUtmMappingData;
use App\Enums\IntegrationCategory;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use App\Factories\IntegrationSettingsFactory;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectBonusGuaranteeForm;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectUtmMappingForm;
use App\Exceptions\CallibriApiException;
use App\Services\CallibriService;
use App\Services\ClientService;
use App\Services\IntegrationService;
use App\Services\ProjectService;
use App\Services\PromotionRegionService;
use App\Services\PromotionTopicService;
use App\Services\UserService;
use App\Services\YandexDirectService;
use App\Services\YandexSearchApiPhraseParser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Domain\Clients\ClientRepositoryInterface;

new
#[Layout('layouts::system-settings')]
#[Title('Создание проекта')]
class extends Component
{
    use WithFileUploads;

    public CreateClientProjectForm $clientProjectForm;
    public ProjectBonusGuaranteeForm $bonusGuaranteeForm;
    public ProjectUtmMappingForm $utmMappingForm;

    private ClientRepositoryInterface $clientRepository;

    private ClientService $clientService;
    private ProjectService $projectService;
    private PromotionRegionService $promotionRegionService;
    private PromotionTopicService $promotionTopicService;
    private IntegrationService $integrationService;
    private UserService $userService;

    public Collection $clients;
    public Collection $promotionRegions;
    public Collection $promotionTopics;

    public ?ProjectIntegrationData $selectedIntegration = null;

    public Collection $integrationSettings;

    public int $yandexDirectOAuthRevision = 0;

    public $phraseDocxFile;

    public function boot(
        ClientService $clientService,
        ProjectService $projectService,
        PromotionRegionService $promotionRegionService,
        PromotionTopicService $promotionTopicService,
        IntegrationService $integrationService,
        UserService $userService,
        ClientRepositoryInterface $clientRepository
    )
    {
        $this->clientService = $clientService;
        $this->projectService = $projectService;
        $this->promotionRegionService = $promotionRegionService;
        $this->promotionTopicService = $promotionTopicService;
        $this->integrationService = $integrationService;
        $this->userService = $userService;
        $this->clientRepository = $clientRepository;
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
            $client = $this->clientRepository->findById($project->client_id);
            
            $this->clientProjectForm->from($project);
            $this->clientProjectForm->manager = $client->getManagerId();
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
                $this->restoreFromOAuthCache($cachedData);
            }

            foreach ($state['integrations'] as $setting) {
                $integrationData = new ProjectIntegrationData();
                $integrationData->integration = IntegrationData::from($setting['integration']);
                $integrationData->settings = $setting['settings'];
                $integrationData->isEnabled = $setting['isEnabled'];
                $this->integrationSettings[$integrationData->integration->id] = $integrationData;
            }

            if (! empty($state['open_integration'])) {
                $this->selectIntegration($state['open_integration']);
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

    #[Computed]
    public function isYandexSearchApiConfigured(): bool
    {
        return filled(config('services.yandex_search_api.api_key'))
            && filled(config('services.yandex_search_api.folder_id'));
    }

    #[Computed]
    public function isYandexDirectOAuthConfigured(): bool
    {
        return filled(config('services.yandex_direct.client_id'))
            && filled(config('services.yandex_direct.client_secret'));
    }

    #[Computed]
    public function isSelectedIntegrationPlatformConfigured(): bool
    {
        $code = $this->selectedIntegration?->integration->code ?? null;

        return match ($code) {
            'yandex_search_api' => $this->isYandexSearchApiConfigured,
            'yandex_direct' => $this->isYandexDirectOAuthConfigured,
            default => true,
        };
    }

    public function selectIntegration(string $code)
    {
        $integration = $this->integrations()->firstWhere('code', $code);

        if ($integration === null) {
            return;
        }

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

        $listModalName = match ($integration->category) {
            IntegrationCategory::TOOLS => 'tools-integrations-modal',
            IntegrationCategory::MONEY => 'money-integrations-modal',
            IntegrationCategory::ANALYTICS => 'analytics-integrations-modal',
        };

        $this->dispatch('modal-hide', name: $listModalName);
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


    public function loadCallibriProjects(string $email, string $token, ?string $includeSiteId = null): array
    {
        if (trim($email) === '' || trim($token) === '') {
            return ['error' => 'Укажите email и API token'];
        }

        try {
            $projects = app(CallibriService::class)
                ->listSites(
                    $email,
                    $token,
                    $includeSiteId !== null && $includeSiteId !== '' ? (int) $includeSiteId : null
                )
                ->map(fn (array $site) => [
                    'value' => (string) $site['id'],
                    'label' => $site['label'],
                ])
                ->values()
                ->all();

            return ['projects' => $projects];
        } catch (CallibriApiException $e) {
            report($e);

            return ['error' => 'Не удалось загрузить проекты Callibri. Проверьте email и token.'];
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось загрузить проекты Callibri.'];
        }
    }

    public function testCallibriIntegration(array $settings, string $date): array
    {
        if (trim($settings['email'] ?? '') === '' || trim($settings['token'] ?? '') === '') {
            return ['error' => 'Укажите email и API token'];
        }

        if (empty($settings['site_id'])) {
            return ['error' => 'Выберите проект Callibri'];
        }

        if (empty($settings['appeals_type'])) {
            return ['error' => 'Выберите хотя бы один тип обращений'];
        }

        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);

            if ($parsedDate === false) {
                $parsedDate = Carbon::createFromFormat('d.m.Y', $date);
            }

            if ($parsedDate === false) {
                return ['error' => 'Укажите корректную дату'];
            }

            $count = app(CallibriService::class)->countLeadsForDate($settings, $parsedDate);

            return ['count' => $count];
        } catch (CallibriApiException $e) {
            return ['error' => 'Ошибка API Callibri. Проверьте настройки интеграции.'];
        } catch (\Throwable $e) {
            return ['error' => 'Не удалось проверить интеграцию.'];
        }
    }


    /**
     * @return array{url?: string, cache_data_id?: string, error?: string}
     */
    public function prepareYandexDirectOAuth(bool $popup = true): array
    {
        $this->ensureSelectedIntegration('yandex_direct');

        if (! $this->isYandexDirectOAuthConfigured) {
            return [
                'error' => 'Интеграция Яндекс.Директ не настроена на сервере. Обратитесь к администратору.',
            ];
        }

        $cacheDataId = (string) Str::uuid();

        Cache::put(
            'integration_data_'.$cacheDataId,
            $this->buildOAuthCachePayload(),
            now()->addMinutes(15)
        );

        $url = route('yandex_direct.oauth.redirect', [
            'project_id' => $this->clientProjectForm->id,
            'cache_data_id' => $cacheDataId,
            'popup' => $popup ? 1 : 0,
        ]);

        return [
            'url' => $url,
            'cache_data_id' => $cacheDataId,
        ];
    }

    /**
     * @return array{settings?: array<string, mixed>, pending?: bool}
     */
    public function pullYandexDirectOAuthResult(string $cacheDataId): array
    {
        if (trim($cacheDataId) === '') {
            return ['pending' => true];
        }

        $settings = Cache::pull('yandex_direct_oauth_result_'.$cacheDataId);

        if (! is_array($settings) || $settings === []) {
            return ['pending' => true];
        }

        return ['settings' => $settings];
    }

    /**
     * @return array{applied?: bool, pending?: bool}
     */
    public function finalizeYandexDirectOAuth(string $cacheDataId): array
    {
        $cacheDataId = trim($cacheDataId);

        if ($cacheDataId === '') {
            return ['pending' => true];
        }

        $settings = Cache::pull('yandex_direct_oauth_result_'.$cacheDataId);

        if (! is_array($settings) || $settings === []) {
            return ['pending' => true];
        }

        $this->selectIntegration('yandex_direct');
        $this->applyYandexDirectOAuthTokens($settings);
        $this->dispatch('modal-show', name: 'integration-settings-modal');

        return ['applied' => true];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyYandexDirectOAuthFromBroadcast(array $settings, ?int $integrationId = null): void
    {
        $this->selectIntegration('yandex_direct');

        if ($integrationId !== null
            && $this->selectedIntegration?->integration?->id !== $integrationId) {
            return;
        }

        if ($settings === []) {
            return;
        }

        $this->applyYandexDirectOAuthTokens($settings);
        $this->dispatch('modal-show', name: 'integration-settings-modal');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    #[On('yandex-direct-oauth-received')]
    public function handleYandexDirectOAuthReceived(
        ?array $settings = null,
        ?string $cacheDataId = null,
        ?int $integrationId = null
    ): void {
        if (is_array($settings) && $settings !== []) {
            $this->applyYandexDirectOAuthFromBroadcast($settings, $integrationId);

            return;
        }

        if (filled($cacheDataId)) {
            $this->finalizeYandexDirectOAuth($cacheDataId);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyYandexDirectOAuthTokens(array $settings): void
    {
        $this->ensureSelectedIntegration('yandex_direct');

        if ($this->selectedIntegration?->integration === null) {
            return;
        }

        $integrationId = $this->selectedIntegration->integration->id;
        $existingToken = (string) ($this->selectedIntegration->settings['oauth_token'] ?? '');
        $newToken = (string) ($settings['oauth_token'] ?? '');

        if ($existingToken !== '' && $existingToken === $newToken && ($this->selectedIntegration->isEnabled ?? false)) {
            $mergedSettings = $this->selectedIntegration->settings ?? [];
        } else {
            $mergedSettings = array_merge(
                $this->selectedIntegration->settings ?? [],
                [
                    'oauth_token' => $settings['oauth_token'] ?? null,
                    'refresh_token' => $settings['refresh_token'] ?? null,
                    'token_expires_at' => $settings['token_expires_at'] ?? null,
                    'sync_enabled_at' => now()->format('Y-m-d'),
                ],
                $this->extractYandexDirectOAuthProfile($settings)
            );

            $this->selectedIntegration->isEnabled = true;
            $this->selectedIntegration->settings = $mergedSettings;

            $projectIntegrationData = new ProjectIntegrationData();
            $projectIntegrationData->integration = $this->selectedIntegration->integration;
            $projectIntegrationData->isEnabled = true;
            $projectIntegrationData->settings = $mergedSettings;
            $this->integrationSettings[$integrationId] = $projectIntegrationData;

            $this->yandexDirectOAuthRevision++;
        }

        $oauthToken = (string) ($mergedSettings['oauth_token'] ?? '');
        $loginsResult = $oauthToken !== ''
            ? $this->loadYandexDirectLogins($oauthToken)
            : ['error' => 'Сначала авторизуйтесь через Яндекс.Директ'];

        $this->dispatch(
            'yandex-direct-oauth-applied',
            settings: array_merge(
                [
                    'oauth_token' => $mergedSettings['oauth_token'] ?? null,
                    'refresh_token' => $mergedSettings['refresh_token'] ?? null,
                    'token_expires_at' => $mergedSettings['token_expires_at'] ?? null,
                    'sync_enabled_at' => $mergedSettings['sync_enabled_at'] ?? null,
                    'is_enabled' => true,
                ],
                $this->extractYandexDirectOAuthProfile($mergedSettings)
            ),
            logins: $loginsResult['logins'] ?? [],
            loginsError: $loginsResult['error'] ?? null,
            integrationId: $integrationId,
        );
    }

    /**
     * @return array{
     *     profile?: array<string, string|null>,
     *     error?: string
     * }
     */
    public function loadYandexDirectOAuthProfile(string $oauthToken): array
    {
        if (trim($oauthToken) === '') {
            return ['error' => 'Сначала авторизуйтесь через Яндекс.Директ'];
        }

        try {
            $profile = app(YandexDirectService::class)->fetchOauthUserProfile($oauthToken);
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось получить данные аккаунта Яндекса'];
        }

        if ($profile === null) {
            return ['error' => 'Не удалось получить данные аккаунта Яндекса'];
        }

        $this->ensureSelectedIntegration('yandex_direct');

        if ($this->selectedIntegration?->integration !== null) {
            $integrationId = $this->selectedIntegration->integration->id;
            $mergedSettings = array_merge(
                $this->selectedIntegration->settings ?? [],
                $profile
            );

            $this->selectedIntegration->settings = $mergedSettings;

            if ($this->integrationSettings->has($integrationId)) {
                $this->integrationSettings[$integrationId]->settings = $mergedSettings;
            } else {
                $projectIntegrationData = new ProjectIntegrationData();
                $projectIntegrationData->integration = $this->selectedIntegration->integration;
                $projectIntegrationData->isEnabled = $this->selectedIntegration->isEnabled ?? false;
                $projectIntegrationData->settings = $mergedSettings;
                $this->integrationSettings[$integrationId] = $projectIntegrationData;
            }
        }

        return ['profile' => $profile];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string|null>
     */
    private function extractYandexDirectOAuthProfile(array $settings): array
    {
        return [
            'oauth_yandex_user_id' => isset($settings['oauth_yandex_user_id'])
                ? (string) $settings['oauth_yandex_user_id']
                : null,
            'oauth_yandex_login' => isset($settings['oauth_yandex_login'])
                ? (string) $settings['oauth_yandex_login']
                : null,
            'oauth_yandex_display_name' => isset($settings['oauth_yandex_display_name'])
                ? (string) $settings['oauth_yandex_display_name']
                : null,
            'oauth_yandex_avatar_url' => isset($settings['oauth_yandex_avatar_url'])
                ? (string) $settings['oauth_yandex_avatar_url']
                : null,
        ];
    }

    /**
     * @return array{logins?: array<int, array{value: string, label: string}>, error?: string}
     */
    public function loadYandexDirectLogins(string $oauthToken): array
    {
        if (trim($oauthToken) === '') {
            return ['error' => 'Сначала авторизуйтесь через Яндекс.Директ'];
        }

        try {
            $resolved = app(YandexDirectService::class)->resolveClientLogins($oauthToken);

            $logins = $resolved['logins']
                ->map(fn (array $login) => [
                    'value' => (string) $login['value'],
                    'label' => (string) $login['label'],
                ])
                ->values()
                ->all();

            if ($logins === []) {
                return [
                    'error' => $resolved['error'] ?? 'Не найдено доступных логинов Яндекс.Директ',
                ];
            }

            return ['logins' => $logins];
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось загрузить логины Яндекс.Директ'];
        }
    }

    private function buildOAuthCachePayload(): array
    {
        return [
            'integrationSettings' => $this->integrationSettings
                ->map(fn (ProjectIntegrationData $item) => $item->toArray())
                ->all(),
            'clientProjectForm' => $this->clientProjectForm->all(),
            'bonusGuaranteeForm' => $this->bonusGuaranteeForm->all(),
            'utmMappingForm' => $this->utmMappingForm->all(),
        ];
    }

    private function restoreFromOAuthCache(array $cachedData): void
    {
        if (isset($cachedData['clientProjectForm']) && is_array($cachedData['clientProjectForm'])) {
            $this->clientProjectForm->fill($cachedData['clientProjectForm']);
        }

        if (isset($cachedData['bonusGuaranteeForm']) && is_array($cachedData['bonusGuaranteeForm'])) {
            $this->bonusGuaranteeForm->fill($cachedData['bonusGuaranteeForm']);
        }

        if (isset($cachedData['utmMappingForm']) && is_array($cachedData['utmMappingForm'])) {
            $this->utmMappingForm->fill($cachedData['utmMappingForm']);
        }

        if (! isset($cachedData['integrationSettings']) || ! is_array($cachedData['integrationSettings'])) {
            return;
        }

        foreach ($cachedData['integrationSettings'] as $id => $setting) {
            if (! is_array($setting)) {
                continue;
            }

            $integrationData = new ProjectIntegrationData();
            $integrationData->integration = IntegrationData::from($setting['integration']);
            $integrationData->settings = $setting['settings'] ?? [];
            $integrationData->isEnabled = $setting['isEnabled'] ?? false;
            $this->integrationSettings[$id] = $integrationData;
        }
    }

    /**
     * @return array{phrases: string[], error?: string}
     */
    public function parsePhrasesFromDocx(): array
    {
        $this->validate([
            'phraseDocxFile' => 'required|file|mimes:docx|max:5120',
        ]);

        try {
            $phrases = app(YandexSearchApiPhraseParser::class)
                ->parseFromPath($this->phraseDocxFile->getRealPath());
        } catch (\Throwable $exception) {
            $this->reset('phraseDocxFile');

            return ['phrases' => [], 'error' => $exception->getMessage()];
        }

        $this->reset('phraseDocxFile');

        if ($phrases === []) {
            return ['phrases' => [], 'error' => 'В файле не найдено фраз.'];
        }

        return ['phrases' => $phrases];
    }

    private function ensureSelectedIntegration(string $code): void
    {
        if ($this->selectedIntegration?->integration?->code === $code) {
            return;
        }

        $integration = $this->integrations()->firstWhere('code', $code);

        if ($integration === null) {
            return;
        }

        if ($this->selectedIntegration === null) {
            $this->selectedIntegration = new ProjectIntegrationData();
            $this->selectedIntegration->isEnabled = false;
            $this->selectedIntegration->settings = [];
        }

        $this->selectedIntegration->integration = IntegrationData::from($integration);
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