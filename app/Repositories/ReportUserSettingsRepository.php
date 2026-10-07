<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReportUserSettingsRepository
{
    private const CHANNELS_TABLE = 'channel_report_user_settings';

    private const STATISTICS_TABLE = 'statistics_report_user_settings';

    public function getChannelSettings(int $userId): ?string
    {
        return $this->get(self::CHANNELS_TABLE, $userId);
    }

    public function saveChannelSettings(int $userId, string $settingsJson): void
    {
        $this->save(self::CHANNELS_TABLE, $userId, $settingsJson);
    }

    public function getStatisticsSettings(int $userId): ?string
    {
        return $this->get(self::STATISTICS_TABLE, $userId);
    }

    public function saveStatisticsSettings(int $userId, string $settingsJson): void
    {
        $this->save(self::STATISTICS_TABLE, $userId, $settingsJson);
    }

    private function get(string $table, int $userId): ?string
    {
        $settings = DB::table($table)
            ->where('user_id', $userId)
            ->value('settings');

        return filled($settings) ? (string) $settings : null;
    }

    private function save(string $table, int $userId, string $settingsJson): void
    {
        DB::table($table)->updateOrInsert(
            ['user_id' => $userId],
            ['settings' => $settingsJson]
        );
    }
}
