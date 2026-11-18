<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class StatisticsReportAgencySettingsRepository
{
    private const TABLE = 'statistics_report_agency_settings';

    public function get(int $agencyId): ?array
    {
        $settings = DB::table(self::TABLE)
            ->where('agency_id', $agencyId)
            ->value('settings');

        if (! filled($settings)) {
            return null;
        }

        $decoded = is_string($settings) ? json_decode($settings, true) : $settings;

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function save(int $agencyId, array $settings): void
    {
        $payload = [
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        $exists = DB::table(self::TABLE)->where('agency_id', $agencyId)->exists();

        if ($exists) {
            DB::table(self::TABLE)->where('agency_id', $agencyId)->update($payload);

            return;
        }

        DB::table(self::TABLE)->insert([
            'agency_id' => $agencyId,
            ...$payload,
            'created_at' => now(),
        ]);
    }
}
