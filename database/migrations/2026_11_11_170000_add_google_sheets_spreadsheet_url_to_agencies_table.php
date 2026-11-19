<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->text('google_sheets_spreadsheet_url')->nullable()->after('bitrix24_webhook');
        });

        $this->migrateSpreadsheetUrlsFromProjects();
        $this->stripLegacyGoogleSheetsProjectSettings();
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('google_sheets_spreadsheet_url');
        });
    }

    private function migrateSpreadsheetUrlsFromProjects(): void
    {
        $integrationId = DB::table('integrations')->where('code', 'google_sheets')->value('id');

        if ($integrationId === null) {
            return;
        }

        $rows = DB::table('integration_project')
            ->where('integration_id', $integrationId)
            ->get(['project_id', 'settings']);

        $documentIdsByAgency = [];

        foreach ($rows as $row) {
            $settings = $this->decodeSettings($row->settings);
            $documentId = $this->extractDocumentId($settings);

            if ($documentId === '') {
                continue;
            }

            $agencyIds = $this->agencyIdsForProject((int) $row->project_id);

            if ($agencyIds === []) {
                $agencyIds = DB::table('agencies')->orderBy('id')->limit(1)->pluck('id')->all();
            }

            foreach ($agencyIds as $agencyId) {
                $documentIdsByAgency[(int) $agencyId][] = $documentId;
            }
        }

        foreach ($documentIdsByAgency as $agencyId => $documentIds) {
            $unique = array_values(array_unique($documentIds));

            if (count($unique) !== 1) {
                continue;
            }

            DB::table('agencies')
                ->where('id', $agencyId)
                ->update([
                    'google_sheets_spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$unique[0],
                    'updated_at' => now(),
                ]);
        }
    }

    private function stripLegacyGoogleSheetsProjectSettings(): void
    {
        $integrationId = DB::table('integrations')->where('code', 'google_sheets')->value('id');

        if ($integrationId === null) {
            return;
        }

        $legacyKeys = [
            'document_id',
            'documentId',
            'oauth_token',
            'oauthToken',
            'refresh_token',
            'refreshToken',
            'token_expires_at',
            'tokenExpiresAt',
            'oauth_google_user_id',
            'oauth_google_email',
            'oauth_google_display_name',
            'oauth_google_avatar_url',
            'oauthGoogleUserId',
            'oauthGoogleEmail',
            'oauthGoogleDisplayName',
            'oauthGoogleAvatarUrl',
        ];

        $rows = DB::table('integration_project')
            ->where('integration_id', $integrationId)
            ->get(['id', 'settings']);

        foreach ($rows as $row) {
            $settings = $this->decodeSettings($row->settings);

            if ($settings === []) {
                continue;
            }

            $changed = false;

            foreach ($legacyKeys as $key) {
                if (array_key_exists($key, $settings)) {
                    unset($settings[$key]);
                    $changed = true;
                }
            }

            if (! $changed) {
                continue;
            }

            DB::table('integration_project')
                ->where('id', $row->id)
                ->update([
                    'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @return list<int>
     */
    private function agencyIdsForProject(int $projectId): array
    {
        $specialistId = DB::table('projects')->where('id', $projectId)->value('specialist_id');

        if ($specialistId === null) {
            return [];
        }

        return DB::table('agency_user')
            ->where('user_id', $specialistId)
            ->pluck('agency_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSettings(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function extractDocumentId(array $settings): string
    {
        $value = trim((string) ($settings['document_id'] ?? $settings['documentId'] ?? ''));

        if ($value === '') {
            return '';
        }

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9\-_]+)#', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }
};
