<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnableGoogleSheetsForSeoProjectsCommand extends Command
{
    protected $signature = 'google-sheets:enable-for-seo-projects';

    protected $description = 'Одноразово включает Google Таблицы у SEO-проектов без строки integration_project';

    public function handle(): int
    {
        $integrationId = DB::table('integrations')->where('code', 'google_sheets')->value('id');

        if ($integrationId === null) {
            $this->warn('Интеграция google_sheets не найдена в справочнике.');

            return self::FAILURE;
        }

        $existingProjectIds = DB::table('integration_project')
            ->where('integration_id', $integrationId)
            ->pluck('project_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $seoProjectIds = DB::table('projects')
            ->where('project_type', 'seo_promotion')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = array_values(array_diff($seoProjectIds, $existingProjectIds));
        $now = now();
        $inserted = 0;

        foreach ($missing as $projectId) {
            DB::table('integration_project')->insert([
                'integration_id' => $integrationId,
                'project_id' => $projectId,
                'is_enabled' => 1,
                'settings' => json_encode([
                    'sync_enabled_at' => $now->toDateString(),
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        $this->info(sprintf(
            'Google Sheets for SEO projects: inserted=%d, already_present=%d',
            $inserted,
            count($seoProjectIds) - $inserted,
        ));

        return self::SUCCESS;
    }
}
