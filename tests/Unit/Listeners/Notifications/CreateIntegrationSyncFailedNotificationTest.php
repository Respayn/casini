<?php

namespace Tests\Unit\Listeners\Notifications;

use App\Events\Notifications\IntegrationSyncFailed;
use App\Listeners\Notifications\CreateIntegrationSyncFailedNotification;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateIntegrationSyncFailedNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creates_notification_with_error_time_and_project_link(): void
    {
        $agency = Agency::query()->orderBy('id')->first();
        if ($agency === null) {
            Agency::factory()->create(['time_zone' => 'Asia/Yekaterinburg']);
        } else {
            $agency->update(['time_zone' => 'Asia/Yekaterinburg']);
        }

        Carbon::setTestNow(Carbon::parse('2026-08-13 09:05:00', 'UTC'));

        $specialist = User::factory()->create([
            'is_active' => true,
            'enable_important_notifications' => true,
        ]);
        $client = Client::factory()->create(['manager_id' => $specialist->id]);
        $project = Project::factory()->create([
            'name' => 'Сайт клиента',
            'client_id' => $client->id,
            'specialist_id' => $specialist->id,
            'is_active' => true,
        ]);

        app(CreateIntegrationSyncFailedNotification::class)->handle(
            new IntegrationSyncFailed(
                projectId: $project->id,
                error: 'Не удалось получить расход в Директе: timeout',
                collector: 'yandex_direct_daily_spend',
            )
        );

        $notification = Notification::query()
            ->where('user_id', $specialist->id)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            'Не удалось получить расход в Директе: timeout, 14:05, 13.08.26, [[proj]]',
            $notification->text
        );
        $this->assertSame($project->id, $notification->project_id);
        $this->assertNull($notification->read_at);
        $this->assertSame('Сайт клиента', data_get($notification->links, '0.label'));
        $this->assertSame('system-settings.clients-and-projects.projects.manage', data_get($notification->links, '0.route'));

        Carbon::setTestNow();
    }
}
