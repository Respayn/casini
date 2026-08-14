<?php

namespace App\Data\IntegrationSettings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class YandexMetrikaIntegrationSettingsData extends IntegrationSettingsData
{
    public const DEFAULT_DATA_MODE = 'without_robots';

    public const DEFAULT_ATTRIBUTION_MODEL = 'automatic';

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

    public ?string $encryptedOauthToken = null;

    public ?string $encryptedRefreshToken = null;

    public ?string $tokenExpiresAt = null;

    public array $goals = [];

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

        $oauthToken = $settings->get('oauth_token');
        $refreshToken = $settings->get('refresh_token');

        $data->encryptedOauthToken = filled($oauthToken) ? Crypt::encryptString((string) $oauthToken) : null;
        $data->encryptedRefreshToken = filled($refreshToken) ? Crypt::encryptString((string) $refreshToken) : null;
        $data->tokenExpiresAt = $settings->get('token_expires_at');
        $data->goals = $settings->get('goals', []);
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

    public function getDecryptedOauthToken(): string
    {
        return Crypt::decryptString($this->encryptedOauthToken);
    }

    public function getDecryptedRefreshToken(): string
    {
        return Crypt::decryptString($this->encryptedRefreshToken);
    }
}
