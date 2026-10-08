<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NightSyncCollectorsRegistrationTest extends TestCase
{
    public function test_default_collectors_include_metrika_and_google_sheets(): void
    {
        $keys = array_map(
            fn (IntegrationSyncCollector $collector) => $collector->key(),
            IntegrationSyncDispatcher::defaultCollectors(),
        );

        foreach (['yandex_direct_daily_spend', 'callibri_daily_leads', 'yandex_metrika', 'google_sheets'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_schedule_has_no_separate_metrika_or_google_sheets_commands(): void
    {
        Artisan::call('schedule:list');

        $commands = array_map(
            fn (Event $event) => (string) $event->command,
            app(Schedule::class)->events(),
        );

        $this->assertNotEmpty(array_filter(
            $commands,
            fn (string $command) => str_contains($command, 'integrations:dispatch-due-syncs'),
        ));

        foreach ($commands as $command) {
            $this->assertStringNotContainsString('metrika:sync', $command);
            $this->assertStringNotContainsString('google-sheets:sync', $command);
        }
    }
}
