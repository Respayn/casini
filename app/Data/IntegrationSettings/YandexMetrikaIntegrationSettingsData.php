<?php

namespace App\Data\IntegrationSettings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class YandexMetrikaIntegrationSettingsData extends IntegrationSettingsData
{
    public const DEFAULT_DATA_MODE = 'without_robots';

    public const DEFAULT_ATTRIBUTION_MODEL = 'automatic';

    public const GOALS_METRIC_TARGET_VISITS = 'target_visits';

    public const GOALS_METRIC_GOAL_REACHES = 'goal_reaches';

    public const DEFAULT_GOALS_METRIC = self::GOALS_METRIC_TARGET_VISITS;

    /**
     * @var array<string, bool>
     */
    public const DEFAULT_REPORTS = [
        'goals_search_engines' => false,
        'goals_utm' => false,
        'goals_conversions' => false,
        'goals_direct_summary' => false,
        'visits_search_engines' => false,
        'visits_search_queries' => false,
        'visits_geo' => false,
    ];

    public ?int $counterId = null;

    public ?string $counterDomain = null;

    public ?string $counterTimeZone = null;

    public ?string $encryptedOauthToken = null;

    public ?string $encryptedRefreshToken = null;

    public ?string $tokenExpiresAt = null;

    /**
     * @var list<int>
     */
    public array $goals = [];

    public string $goalsMetric = self::DEFAULT_GOALS_METRIC;

    public string $attributionModel = self::DEFAULT_ATTRIBUTION_MODEL;

    public string $dataMode = self::DEFAULT_DATA_MODE;

    /**
     * @var array{entry_page: ?string, last_search_phrase: ?string, geo: ?string}
     */
    public array $filters = [
        'entry_page' => null,
        'last_search_phrase' => null,
        'geo' => null,
    ];

    /**
     * @var array<string, bool>
     */
    public array $reports = self::DEFAULT_REPORTS;

    public static function fromSettings(Collection $settings): self
    {
        $data = new self();
        $data->counterId = $settings->get('counter_id') !== null
            ? (int) $settings->get('counter_id')
            : null;
        $data->counterDomain = $settings->get('counter_domain');
        $counterTimeZone = $settings->get('counter_time_zone');
        $data->counterTimeZone = filled($counterTimeZone) ? (string) $counterTimeZone : null;

        $oauthToken = $settings->get('oauth_token');
        $refreshToken = $settings->get('refresh_token');

        $data->encryptedOauthToken = filled($oauthToken) ? Crypt::encryptString((string) $oauthToken) : null;
        $data->encryptedRefreshToken = filled($refreshToken) ? Crypt::encryptString((string) $refreshToken) : null;
        $data->tokenExpiresAt = $settings->get('token_expires_at');
        $data->goals = self::normalizeGoalIds($settings->get('goals', []));
        $data->goalsMetric = self::normalizeGoalsMetric($settings->get('goals_metric'));
        $data->attributionModel = (string) $settings->get('attribution_model', self::DEFAULT_ATTRIBUTION_MODEL);
        $data->dataMode = (string) $settings->get('data_mode', self::DEFAULT_DATA_MODE);
        $data->filters = array_merge(
            [
                'entry_page' => null,
                'last_search_phrase' => null,
                'geo' => null,
            ],
            is_array($settings->get('filters')) ? $settings->get('filters') : []
        );
        $savedReports = is_array($settings->get('reports')) ? $settings->get('reports') : [];
        $data->reports = array_merge(
            self::DEFAULT_REPORTS,
            array_map(fn ($value) => (bool) $value, $savedReports)
        );

        return $data;
    }

    /**
     * @return list<int>
     */
    public static function normalizeGoalIds(mixed $goals): array
    {
        if (! is_array($goals)) {
            return [];
        }

        $ids = [];
        foreach ($goals as $goal) {
            $id = (int) $goal;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function normalizeGoalsMetric(mixed $metric): string
    {
        $value = trim((string) $metric);

        return in_array($value, [self::GOALS_METRIC_TARGET_VISITS, self::GOALS_METRIC_GOAL_REACHES], true)
            ? $value
            : self::DEFAULT_GOALS_METRIC;
    }

    public function getDecryptedOauthToken(): string
    {
        return Crypt::decryptString($this->encryptedOauthToken);
    }

    public function getDecryptedRefreshToken(): string
    {
        return Crypt::decryptString($this->encryptedRefreshToken);
    }
}
