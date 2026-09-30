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
            ."The exception occurred while attempting to log: Failed to get daily expenses";

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
    }
}
