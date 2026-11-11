<?php

namespace Tests\Unit\Listeners\Notifications;

use App\Events\Notifications\IntegrationSyncFailed;
use App\Listeners\Notifications\CreateIntegrationSyncFailedNotification;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CreateIntegrationSyncFailedNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creates_notification_with_short_text_and_raw_error_detail(): void
    {
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

        $rawError = "The stream or file \"/tmp/x.log\" could not be opened in append mode: Failed to open stream: Permission denied\n"
            .'The exception occurred while attempting to log: Failed to get daily expenses';

        app(CreateIntegrationSyncFailedNotification::class)->handle(
            new IntegrationSyncFailed(
                projectId: $project->id,
                error: $rawError,
                collector: 'yandex_direct_daily_spend',
            )
        );

        $notification = Notification::query()
            ->where('user_id', $specialist->id)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Failed to get daily expenses', $notification->text);
        $this->assertSame($rawError, data_get($notification->payload, 'error_detail'));
        $this->assertSame($project->id, $notification->project_id);
        $this->assertSame('Сайт клиента', data_get($notification->payload, 'project'));
        $this->assertNull($notification->read_at);
        $this->assertFalse((bool) data_get($notification->payload, 'inline_meta'));
        $this->assertNull(data_get($notification->payload, 'access_error'));
    }

    public function test_google_access_error_gets_human_title(): void
    {
        [$specialist, $project] = $this->projectWithSpecialist();

        $rawError = 'Не удалось получить расходы Google Таблиц: Failed to refresh Google token: {"error":"invalid_grant"}';

        app(CreateIntegrationSyncFailedNotification::class)->handle(
            new IntegrationSyncFailed(
                projectId: $project->id,
                error: $rawError,
                collector: 'google_sheets',
            )
        );

        $notification = Notification::query()
            ->where('user_id', $specialist->id)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.',
            $notification->text
        );
        $this->assertSame($rawError, data_get($notification->payload, 'error_detail'));
        $this->assertTrue((bool) data_get($notification->payload, 'access_error'));
        $this->assertSame('google_sheets', data_get($notification->payload, 'integration_code'));
    }

    public function test_direct_oauth_access_error_gets_human_title(): void
    {
        [$specialist, $project] = $this->projectWithSpecialist();

        $rawError = 'Не удалось получить расход в Директе: Invalid request parameters: Недействительный OAuth-токен';

        app(CreateIntegrationSyncFailedNotification::class)->handle(
            new IntegrationSyncFailed(
                projectId: $project->id,
                error: $rawError,
                collector: 'yandex_direct_daily_spend',
            )
        );

        $notification = Notification::query()
            ->where('user_id', $specialist->id)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            'Доступ к Яндекс.Директ больше не действует. Подключите его заново.',
            $notification->text
        );
        $this->assertSame('yandex_direct', data_get($notification->payload, 'integration_code'));
    }

    public function test_timeout_error_keeps_technical_display_text(): void
    {
        [$specialist, $project] = $this->projectWithSpecialist();

        $rawError = 'App\\Jobs\\ProcessIntegrationSyncItem has timed out.';

        app(CreateIntegrationSyncFailedNotification::class)->handle(
            new IntegrationSyncFailed(
                projectId: $project->id,
                error: $rawError,
                collector: 'google_sheets',
            )
        );

        $notification = Notification::query()
            ->where('user_id', $specialist->id)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame($rawError, $notification->text);
        $this->assertNull(data_get($notification->payload, 'access_error'));
    }

    public function test_does_not_duplicate_unread_access_error_for_same_integration(): void
    {
        [$specialist, $project] = $this->projectWithSpecialist();

        $rawError = 'Failed to refresh Google token: invalid_grant';

        $listener = app(CreateIntegrationSyncFailedNotification::class);
        $event = new IntegrationSyncFailed(
            projectId: $project->id,
            error: $rawError,
            collector: 'google_sheets',
        );

        $listener->handle($event);
        $listener->handle($event);

        $count = Notification::query()
            ->where('user_id', $specialist->id)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->where('project_id', $project->id)
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * @return array{0: User, 1: Project}
     */
    private function projectWithSpecialist(): array
    {
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

        return [$specialist, $project];
    }
}
