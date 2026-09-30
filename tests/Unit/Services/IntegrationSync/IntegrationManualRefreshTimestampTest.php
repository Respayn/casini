<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Enums\IntegrationSyncRunStatus;
use App\Models\Agency;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IntegrationManualRefreshTimestampTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-07 12:34:00', 'UTC'));
        config(['app.timezone' => 'UTC']);

        // В рамках транзакции очищаем чужие run'ы, иначе тултип подхватит реальные данные БД.
        IntegrationSyncItem::query()->delete();
        IntegrationSyncRun::query()->delete();

        // Формат тултипа идёт в timezone агентства — фиксируем UTC, чтобы ожидания были стабильны.
        Agency::query()->update(['time_zone' => 'UTC']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_returns_null_when_never_recorded_and_no_sync(): void
    {
        $service = new IntegrationManualRefreshTimestamp();

        $this->assertNull($service->formattedLabel(1, 'channels'));
    }

    public function test_formats_recorded_timestamp(): void
    {
        $service = new IntegrationManualRefreshTimestamp();
        $service->record(7, 'channels');

        $label = $service->formattedLabel(7, 'channels');

        $this->assertNotNull($label);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}, \d{2}\.\d{2}\.\d{2}$/', $label);
    }

    public function test_product_keys_are_independent_for_manual_stamps(): void
    {
        $service = new IntegrationManualRefreshTimestamp();
        $service->record(3, 'statistics');

        $this->assertNotNull($service->formattedLabel(3, 'statistics'));
        $this->assertNull($service->formattedLabel(3, 'channels'));
    }

    public function test_falls_back_to_nightly_sync_finished_at(): void
    {
        $this->createFinishedSyncRun(Carbon::parse('2026-08-07 00:15:00', 'UTC'));

        $service = new IntegrationManualRefreshTimestamp();

        $this->assertSame(
            '00:15, 07.08.26',
            $service->formattedLabel(1, 'channels'),
        );
        $this->assertSame(
            '00:15, 07.08.26',
            $service->formattedLabel(1, 'statistics'),
        );
    }

    public function test_prefers_more_recent_manual_over_nightly(): void
    {
        $this->createFinishedSyncRun(Carbon::parse('2026-08-07 00:15:00', 'UTC'));

        $service = new IntegrationManualRefreshTimestamp();
        $service->record(1, 'channels');

        $this->assertSame(
            '12:34, 07.08.26',
            $service->formattedLabel(1, 'channels'),
        );
    }

    public function test_prefers_more_recent_nightly_over_stale_manual(): void
    {
        Cache::forever(
            'integrations.manual_refresh.last_at.user.1.channels',
            Carbon::parse('2026-08-06 10:00:00', 'UTC')->toIso8601String(),
        );

        $this->createFinishedSyncRun(Carbon::parse('2026-08-07 00:15:00', 'UTC'));

        $service = new IntegrationManualRefreshTimestamp();

        $this->assertSame(
            '00:15, 07.08.26',
            $service->formattedLabel(1, 'channels'),
        );
    }

    private function createFinishedSyncRun(Carbon $finishedAt): void
    {
        IntegrationSyncRun::query()->create([
            'local_date' => $finishedAt->toDateString(),
            'timezone' => 'UTC',
            'target_date' => $finishedAt->copy()->subDay()->toDateString(),
            'status' => IntegrationSyncRunStatus::Completed,
            'started_at' => $finishedAt->copy()->subMinutes(10),
            'finished_at' => $finishedAt,
        ]);
    }
}
