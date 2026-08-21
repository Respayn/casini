<?php

namespace App\Console\Commands;

use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Models\IntegrationProject;
use App\Services\YandexMetrikaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Throwable;

class SyncYandexMetrikaSearchQueriesVisitsCommand extends Command
{
    protected $signature = 'metrika:sync-search-queries-visits';

    protected $description = 'Ночной съём переходов из отчёта «Поисковые запросы» Яндекс Метрики';

    public function handle(
        YandexMetrikaService $metrikaService,
        YandexMetrikaRepositoryInterface $repository
    ): int {
        $integrations = IntegrationProject::query()
            ->with(['integration', 'project.specialist.agencies'])
            ->where('is_enabled', true)
            ->whereHas('integration', fn ($query) => $query->where('code', 'yandex_metrika'))
            ->get();

        $synced = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($integrations as $integrationProject) {
            $settings = is_array($integrationProject->settings) ? $integrationProject->settings : [];
            $reports = is_array($settings['reports'] ?? null) ? $settings['reports'] : [];

            if (! ($reports['visits_search_queries'] ?? false)) {
                $skipped++;
                continue;
            }

            $token = trim((string) ($settings['oauth_token'] ?? ''));
            $counterId = (int) ($settings['counter_id'] ?? 0);
            $syncEnabledAt = trim((string) ($settings['sync_enabled_at'] ?? ''));

            if ($token === '' || $counterId <= 0 || $syncEnabledAt === '') {
                $skipped++;
                continue;
            }

            try {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $syncEnabledAt);
                if ($dateFrom === false) {
                    $dateFrom = Carbon::parse($syncEnabledAt);
                }
                $dateFrom = $dateFrom->copy()->startOfMonth();
                $dateTo = Carbon::now();

                if ($dateFrom->isAfter($dateTo)) {
                    $skipped++;
                    continue;
                }

                $agencyTimezone = $integrationProject->project?->specialist?->agencies->first()?->time_zone;
                $counterTimezone = filled($settings['counter_time_zone'] ?? null)
                    ? (string) $settings['counter_time_zone']
                    : null;
                $timezone = filled($agencyTimezone) ? (string) $agencyTimezone : $counterTimezone;
                $visitsMetric = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric(
                    $settings['visits_metric'] ?? null
                );

                $metrikaService->setupClientFromSettings($settings);
                $rows = $metrikaService->fetchSearchQueriesVisitsStats(
                    $dateFrom,
                    $dateTo,
                    $visitsMetric,
                    is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
                    (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
                    (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
                    $timezone,
                    $counterTimezone,
                    (string) ($settings['search_queries_minus'] ?? '')
                );

                foreach ($rows as $row) {
                    $repository->upsertSearchQueriesVisits(
                        $integrationProject->project_id,
                        $row['phrase'],
                        $row['month'],
                        $visitsMetric,
                        $row['value']
                    );
                }

                $synced++;
            } catch (Throwable $e) {
                $failed++;
                Log::channel('yandex_metrika')->error('Search queries visits sync failed', [
                    'project_id' => $integrationProject->project_id,
                    'exception' => $e->getMessage(),
                ]);
                $this->error('Проект '.$integrationProject->project_id.': '.$e->getMessage());
            }
        }

        $this->info("Синхронизировано: {$synced}, пропущено: {$skipped}, ошибок: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
