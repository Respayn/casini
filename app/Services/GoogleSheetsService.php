<?php

namespace App\Services;

use App\Models\GoogleSheetsMonthlySpending;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Services\GoogleSheets\GoogleSheetsSpendingsParser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSheetsService
{
    public function __construct(
        private GoogleSheetsAuthService $authService,
        private GoogleSheetsSpendingsParser $parser,
    ) {}

    public static function extractSpreadsheetId(string $value): string
    {
        $value = trim($value);

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9\-_]+)#', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    /**
     * @return Collection<int, GoogleSheetsMonthlySpending>
     */
    public function getSpendingsForProjects(Collection $projectIds, Carbon $month): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        $yearMonth = $month->copy()->startOfMonth()->toDateString();

        return GoogleSheetsMonthlySpending::query()
            ->whereIn('project_id', $projectIds)
            ->whereDate('year_month', $yearMonth)
            ->get()
            ->keyBy('project_id');
    }

    /**
     * @param  array<int>  $projectIds
     * @return array{synced: int, failed: int}
     */
    public function syncProjects(array $projectIds, ?Carbon $month = null): array
    {
        $month ??= Carbon::now()->startOfMonth();
        $synced = 0;
        $failed = 0;

        $integration = Integration::query()->where('code', 'google_sheets')->first();

        if ($integration === null) {
            return ['synced' => 0, 'failed' => count($projectIds)];
        }

        $settings = IntegrationProject::query()
            ->where('integration_id', $integration->id)
            ->where('is_enabled', true)
            ->whereIn('project_id', $projectIds)
            ->get();

        foreach ($settings as $projectIntegration) {
            try {
                $this->syncProjectIntegration($projectIntegration, $month);
                $synced++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    public function syncAllEnabledProjects(?Carbon $month = null): array
    {
        $month ??= Carbon::now()->startOfMonth();

        $integration = Integration::query()->where('code', 'google_sheets')->first();

        if ($integration === null) {
            return ['synced' => 0, 'failed' => 0];
        }

        $projectIds = IntegrationProject::query()
            ->where('integration_id', $integration->id)
            ->where('is_enabled', true)
            ->pluck('project_id')
            ->all();

        return $this->syncProjects($projectIds, $month);
    }

    public function syncProjectIntegration(IntegrationProject $projectIntegration, Carbon $month): GoogleSheetsMonthlySpending
    {
        $settings = is_array($projectIntegration->settings)
            ? $projectIntegration->settings
            : json_decode((string) $projectIntegration->settings, true) ?? [];

        $documentId = self::extractSpreadsheetId((string) ($settings['document_id'] ?? ''));

        if ($documentId === '') {
            throw new \RuntimeException('Google spreadsheet ID is not configured.');
        }

        $accessToken = $this->resolveAccessToken($settings, $projectIntegration);

        $programmingRows = $this->fetchSheetValues($accessToken, $documentId, 'Программинг');
        $copyrightingRows = $this->fetchSheetValues($accessToken, $documentId, 'Копирайтинг');
        $seoRows = $this->fetchSheetValues($accessToken, $documentId, 'SEO-ссылки');

        $programming = $this->parser->parseProgrammingSheet($programmingRows, $month);
        $copyrighting = $this->parser->parseCopyrightingSheet($copyrightingRows, $month);
        $seoSum = $this->parser->parseSeoLinksSheet($seoRows, $month);

        $yearMonth = $month->copy()->startOfMonth()->toDateString();

        return GoogleSheetsMonthlySpending::query()->updateOrCreate(
            [
                'project_id' => $projectIntegration->project_id,
                'year_month' => $yearMonth,
            ],
            [
                'programming_hours' => $programming['hours'],
                'programming_sum' => $programming['sum'],
                'copyrighting_units' => $copyrighting['hours'],
                'copyrighting_sum' => $copyrighting['sum'],
                'seo_links_sum' => $seoSum,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function resolveAccessToken(array $settings, IntegrationProject $projectIntegration): string
    {
        $accessToken = (string) ($settings['oauth_token'] ?? '');
        $refreshToken = (string) ($settings['refresh_token'] ?? '');
        $expiresAt = (string) ($settings['token_expires_at'] ?? '');

        if ($accessToken !== '' && $expiresAt !== '' && now()->lt(Carbon::parse($expiresAt)->subMinute())) {
            return $accessToken;
        }

        if ($refreshToken === '') {
            throw new \RuntimeException('Google OAuth refresh token is missing.');
        }

        $tokens = $this->authService->refreshAccessToken($refreshToken);

        $settings['oauth_token'] = $tokens['access_token'] ?? $accessToken;
        if (isset($tokens['refresh_token'])) {
            $settings['refresh_token'] = $tokens['refresh_token'];
        }
        $settings['token_expires_at'] = now()
            ->addSeconds((int) ($tokens['expires_in'] ?? 3600))
            ->toDateTimeString();

        $projectIntegration->settings = $settings;
        $projectIntegration->save();

        return (string) $settings['oauth_token'];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function fetchSheetValues(string $accessToken, string $spreadsheetId, string $sheetTitle): array
    {
        $sheetTitle = $this->resolveSheetTitle($accessToken, $spreadsheetId, $sheetTitle);
        $range = rawurlencode($sheetTitle).'!A:ZZ';

        $response = Http::withToken($accessToken)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}");

        if (! $response->successful()) {
            throw new \RuntimeException('Google Sheets API error: '.$response->body());
        }

        return $response->json('values') ?? [];
    }

    private function resolveSheetTitle(string $accessToken, string $spreadsheetId, string $expectedTitle): string
    {
        $response = Http::withToken($accessToken)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}", [
                'fields' => 'sheets.properties.title',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Google Sheets metadata error: '.$response->body());
        }

        $titles = collect($response->json('sheets', []))
            ->map(fn (array $sheet) => (string) ($sheet['properties']['title'] ?? ''))
            ->filter();

        $normalizedExpected = $this->normalizeSheetTitle($expectedTitle);

        $match = $titles->first(
            fn (string $title) => $this->normalizeSheetTitle($title) === $normalizedExpected
        );

        if ($match !== null) {
            return $match;
        }

        throw new \RuntimeException("Sheet «{$expectedTitle}» not found.");
    }

    private function normalizeSheetTitle(string $title): string
    {
        return Str::of(mb_strtolower(trim($title)))
            ->replace('ё', 'е')
            ->toString();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    public function mapOAuthProfile(array $profile): array
    {
        return [
            'oauth_google_user_id' => (string) ($profile['id'] ?? ''),
            'oauth_google_email' => (string) ($profile['email'] ?? ''),
            'oauth_google_display_name' => (string) ($profile['name'] ?? $profile['email'] ?? ''),
            'oauth_google_avatar_url' => (string) ($profile['picture'] ?? ''),
        ];
    }
}
