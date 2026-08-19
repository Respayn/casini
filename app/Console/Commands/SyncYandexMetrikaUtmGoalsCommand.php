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

class SyncYandexMetrikaUtmGoalsCommand extends Command
{
    protected $signature = 'metrika:sync-utm-goals';

    protected $description = 'Ночной съём достижений целей из отчёта UTM-метки Яндекс Метрики';

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

            if (! ($reports['goals_utm'] ?? false)) {
                $skipped++;
                continue;
            }

            $token = trim((string) ($settings['oauth_token'] ?? ''));
            $counterId = (int) ($settings['counter_id'] ?? 0);
            $goalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []);
            $syncEnabledAt = trim((string) ($settings['sync_enabled_at'] ?? ''));

            if ($token === '' || $counterId <= 0 || $goalIds === [] || $syncEnabledAt === '') {
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

                $utmFilterMode = YandexMetrikaIntegrationSettingsData::normalizeUtmFilterMode($settings['utm_filter_mode'] ?? null);
                $utmValue = trim((string) ($settings['utm_' . $utmFilterMode] ?? ''));

                $metrikaService->setupClientFromSettings($settings);
                $rows = $metrikaService->fetchUtmGoalsStats(
                    $dateFrom,
                    $dateTo,
                    $goalIds,
                    YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($settings['goals_metric'] ?? null),
                    $utmFilterMode,
                    $utmValue,
                    is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
                    (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
                    (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
                    $timezone,
                    $counterTimezone
                );

                $utmDimensionToField = [
                    'ym:s:UTMSource' => 'utm_source',
                    'ym:s:UTMMedium' => 'utm_medium',
                    'ym:s:UTMCampaign' => 'utm_campaign',
                ];

                $dbRows = [];
                foreach ($rows as $row) {
                    if (($row['value'] ?? 0) <= 0) {
                        continue;
                    }

                    $field = $utmDimensionToField[$row['utm_dimension']] ?? 'utm_source';
                    $dbRow = [
                        'goal_name' => '',
                        'achieved_date' => $row['date'],
                        'utm_source' => null,
                        'utm_medium' => null,
                        'utm_campaign' => null,
                        'utm_content' => null,
                        'utm_term' => null,
                    ];
                    $dbRow[$field] = $row['utm_value'];

                    for ($i = 0; $i < $row['value']; $i++) {
                        $dbRows[] = $dbRow;
                    }
                }

                $repository->replaceGoalUtmRows(
                    $integrationProject->project_id,
                    $dateFrom->format('Y-m-d'),
                    $dateTo->format('Y-m-d'),
                    $dbRows
                );

                $synced++;
            } catch (Throwable $e) {
                $failed++;
                Log::channel('yandex_metrika')->error('UTM goals sync failed', [
                    'project_id' => $integrationProject->project_id,
                    'exception' => $e->getMessage(),
                ]);
                $this->error('Проект ' . $integrationProject->project_id . ': ' . $e->getMessage());
            }
        }

        $this->info("Синхронизировано: {$synced}, пропущено: {$skipped}, ошибок: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
