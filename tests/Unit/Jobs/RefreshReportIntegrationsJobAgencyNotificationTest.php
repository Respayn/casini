<?php

namespace Tests\Unit\Jobs;

use App\Models\Notification;
use App\Repositories\AgencyRepository;
use App\Services\IntegrationSync\IntegrationManualRefreshNotifier;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\NotificationService;
use Mockery;
use Tests\TestCase;

class RefreshReportIntegrationsJobAgencyNotificationTest extends TestCase
{
    public function test_notify_users_creates_shared_notification_for_each_recipient(): void
    {
        $notifications = Mockery::mock(NotificationService::class);
        $captured = [];

        $notifications->shouldReceive('create')
            ->twice()
            ->andReturnUsing(function (...$args) use (&$captured) {
                $captured[] = $args;

                return Mockery::mock(Notification::class);
            });

        $notifier = new IntegrationManualRefreshNotifier(
            $notifications,
            app(AgencyRepository::class),
        );
        $notifier->notifyUsers(
            userIds: [10, 20, 10],
            agencyId: 5,
            startedProduct: 'statistics',
            runId: 'run-1',
            updated: 3,
            failed: 1,
            skipped: 0,
        );

        $this->assertCount(2, $captured);

        foreach ([10, 20] as $index => $userId) {
            $call = $captured[$index];
            $this->assertSame($userId, $call[0]);
            $this->assertSame(
                'Данные в [[channels]] и [[statistics]] обновлены. Ошибок: 1',
                $call[1],
            );
            $this->assertNull($call[2]);
            $this->assertSame(['channels', 'statistics'], array_column($call[3], 'key'));
            $this->assertSame(IntegrationManualRefreshProgress::TYPE_REFRESHED, $call[4]);
            $this->assertSame('both', $call[5]['product']);
            $this->assertSame(5, $call[5]['agency_id']);
            $this->assertSame('statistics', $call[5]['started_product']);
            $this->assertSame(1, $call[5]['failed']);
        }
    }
}
