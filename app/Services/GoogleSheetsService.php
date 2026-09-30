<?php

namespace App\Services;

use App\Events\Notifications\ChannelsInstrumentStopped;
use App\Models\Agency;
use App\Models\GoogleSheetsMonthlySpending;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Services\GoogleSheets\Exceptions\GoogleSheetsParseException;
use App\Services\GoogleSheets\GoogleSheetsSpendingsParser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
     * @return array{synced: int, failed: int, skipped: int}
     */
    public function syncProjects(array $projectIds, ?Carbon $month = null, bool $manual = true): array
    {
        $month ??= Carbon::now()->startOfMonth();
        $synced = 0;
        $failed = 0;
        $skipped = 0;

        $integration = Integration::query()->where('code', 'google_sheets')->first();

        if ($integration === null) {
            return ['synced' => 0, 'failed' => count($projectIds), 'skipped' => 0];
        }

        $settings = IntegrationProject::query()
            ->with(['project.specialist.agencies', 'integration'])
            ->where('integration_id', $integration->id)
            ->where('is_enabled', true)
            ->whereIn('project_id', $projectIds)
            ->get();

        foreach ($settings as $projectIntegration) {
            if ($manual) {
                try {
                    $this->syncProjectIntegration($projectIntegration, $month, manual: true);
                    $synced++;
                } catch (Throwable $e) {
                    report($e);
                    $failed++;
                }

                continue;
            }

            $result = $this->syncProjectIntegrationIfOpenMonth($projectIntegration, $month);

            match ($result) {
                'synced' => $synced++,
                'failed' => $failed++,
                'skipped' => $skipped++,
            };
        }

        return ['synced' => $synced, 'failed' => $failed, 'skipped' => $skipped];
    }

    /**
     * @return array{synced: int, failed: int, skipped: int}
     */
    public function syncOpenMonthForAllEnabledProjects(): array
    {
        $integration = Integration::query()->where('code', 'google_sheets')->first();

        if ($integration === null) {
            return ['synced' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $settings = IntegrationProject::query()
            ->with(['project.specialist.agencies', 'integration'])
            ->where('integration_id', $integration->id)
            ->where('is_enabled', true)
            ->get();

        $synced = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($settings as $projectIntegration) {
            $timezone = $this->resolveAgencyTimezone($projectIntegration);
            $month = Carbon::now($timezone)->startOfMonth();

            $result = $this->syncProjectIntegrationIfOpenMonth($projectIntegration, $month);

            match ($result) {
                'synced' => $synced++,
                'failed' => $failed++,
                'skipped' => $skipped++,
            };
        }

        return ['synced' => $synced, 'failed' => $failed, 'skipped' => $skipped];
    }

    public function isClosedMonth(Carbon $month, ?string $timezone = null): bool
    {
        $timezone ??= (string) config('app.timezone', 'UTC');
        $currentMonth = Carbon::now($timezone)->startOfMonth();

        return $month->copy()->startOfMonth()->lt($currentMonth);
    }

    public function syncProjectIntegration(
        IntegrationProject $projectIntegration,
        Carbon $month,
        bool $manual = false,
    ): GoogleSheetsMonthlySpending {
        $projectIntegration->loadMissing('project.specialist.agencies');

        $project = $projectIntegration->project;

        if ($project === null) {
            throw new \RuntimeException('Project is not configured for Google Sheets integration.');
        }

        if (! $manual && $this->isClosedMonth($month, $this->resolveAgencyTimezone($projectIntegration))) {
            throw new \RuntimeException('Closed month cannot be updated by nightly sync.');
        }

        $settings = is_array($projectIntegration->settings)
            ? $projectIntegration->settings
            : json_decode((string) $projectIntegration->settings, true) ?? [];

        $documentId = self::extractSpreadsheetId((string) ($settings['document_id'] ?? ''));

        if ($documentId === '') {
            throw new \RuntimeException('Google spreadsheet ID is not configured.');
        }

        $yearMonth = $month->copy()->startOfMonth()->toDateString();
        $existing = GoogleSheetsMonthlySpending::query()->firstOrNew([
            'project_id' => $projectIntegration->project_id,
            'year_month' => $yearMonth,
        ]);

        $projectUrl = (string) ($project->domain ?? '');
        $accessToken = $this->resolveAccessToken($settings, $projectIntegration);

        $programming = $this->resolveProgrammingSpendings(
            $accessToken,
            $documentId,
            $month,
            $projectUrl,
            $project,
            $existing,
            $manual,
        );

        $copyrighting = $this->resolveCopyrightingSpendings(
            $accessToken,
            $documentId,
            $month,
            $projectUrl,
            $project,
            $existing,
            $manual,
        );

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
                'seo_links_sum' => 0,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * @return array{hours: float, sum: float}
     */
    private function resolveProgrammingSpendings(
        string $accessToken,
        string $documentId,
        Carbon $month,
        string $projectUrl,
        Project $project,
        GoogleSheetsMonthlySpending $existing,
        bool $manual,
    ): array {
        $fallback = [
            'hours' => (float) ($existing->programming_hours ?? 0),
            'sum' => (float) ($existing->programming_sum ?? 0),
        ];

        try {
            $rows = $this->fetchSheetValues($accessToken, $documentId, 'Программинг');

            return $this->parser->parseProgrammingSheet($rows, $month, $projectUrl);
        } catch (Throwable $e) {
            $this->handleSheetSyncError($project, 'Программинг', $e);

            return $fallback;
        }
    }

    /**
     * @return array{hours: float, sum: float}
     */
    private function resolveCopyrightingSpendings(
        string $accessToken,
        string $documentId,
        Carbon $month,
        string $projectUrl,
        Project $project,
        GoogleSheetsMonthlySpending $existing,
        bool $manual,
    ): array {
        $fallback = [
            'hours' => (float) ($existing->copyrighting_units ?? 0),
            'sum' => (float) ($existing->copyrighting_sum ?? 0),
        ];

        try {
            $rows = $this->fetchSheetValues($accessToken, $documentId, 'Копирайтинг');

            return $this->parser->parseCopyrightingSheet($rows, $month, $projectUrl);
        } catch (Throwable $e) {
            $this->handleSheetSyncError($project, 'Копирайтинг', $e);

            return $fallback;
        }
    }

    private function syncProjectIntegrationIfOpenMonth(
        IntegrationProject $projectIntegration,
        Carbon $month,
    ): string {
        if ($this->isClosedMonth($month, $this->resolveAgencyTimezone($projectIntegration))) {
            return 'skipped';
        }

        try {
            $this->syncProjectIntegration($projectIntegration, $month, manual: false);

            return 'synced';
        } catch (Throwable $e) {
            report($e);

            return 'failed';
        }
    }

    private function handleSheetSyncError(Project $project, string $sheetTitle, Throwable $e): void
    {
        Log::warning('Google Sheets spendings sync sheet failed', [
            'project_id' => $project->id,
            'sheet' => $sheetTitle,
            'exception' => $e->getMessage(),
        ]);

        $specialistId = (int) ($project->specialist_id ?? 0);

        if ($specialistId <= 0) {
            return;
        }

        $message = $e instanceof GoogleSheetsParseException
            ? $e->getMessage()
            : 'Не удалось получить данные из Google Таблицы.';

        $lastSyncedAt = GoogleSheetsMonthlySpending::query()
            ->where('project_id', $project->id)
            ->orderByDesc('synced_at')
            ->value('synced_at');

        event(new ChannelsInstrumentStopped(
            userId: $specialistId,
            projectId: $project->id,
            projectName: $project->name,
            channelId: $project->id,
            channelName: $project->name,
            instrument: 'Google Таблицы ('.$sheetTitle.'): '.$message,
            lastSeenAt: $lastSyncedAt ? Carbon::parse($lastSyncedAt) : null,
        ));
    }

    private function resolveAgencyTimezone(IntegrationProject $projectIntegration): string
    {
        $timezone = $projectIntegration->project?->specialist?->agencies->first()?->time_zone;

        if (filled($timezone)) {
            return (string) $timezone;
        }

        return Agency::query()->value('time_zone')
            ?? (string) config('app.timezone', 'UTC');
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
