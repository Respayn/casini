<?php

namespace Tests\Unit\Services\Notifications;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationRecipientResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectNotificationRecipientResolverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_includes_specialist_and_manager_and_skips_others(): void
    {
        $specialist = User::factory()->create([
            'is_active' => true,
            'enable_important_notifications' => true,
        ]);
        $manager = User::factory()->create([
            'is_active' => true,
            'enable_important_notifications' => true,
        ]);
        $stranger = User::factory()->create([
            'is_active' => true,
            'enable_important_notifications' => true,
        ]);
        $mutedSpecialist = User::factory()->create([
            'is_active' => true,
            'enable_important_notifications' => false,
        ]);
        $inactiveManager = User::factory()->create([
            'is_active' => false,
            'enable_important_notifications' => true,
        ]);

        $client = Client::factory()->create(['manager_id' => $manager->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $specialist->id,
            'is_active' => true,
        ]);

        $ids = app(ProjectNotificationRecipientResolver::class)->userIdsForProject($project->id);

        $this->assertContains($specialist->id, $ids);
        $this->assertContains($manager->id, $ids);
        $this->assertNotContains($stranger->id, $ids);
        $this->assertNotContains($mutedSpecialist->id, $ids);
        $this->assertNotContains($inactiveManager->id, $ids);
    }

    public function test_includes_users_who_can_see_all_client_projects(): void
    {
        $viewer = User::factory()->create([
            'is_active' => true,
            'enable_important_notifications' => true,
        ]);

        Permission::findOrCreate('read clients and projects all', 'web');
        $viewer->givePermissionTo('read clients and projects all');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'is_active' => true,
        ]);

        $ids = app(ProjectNotificationRecipientResolver::class)->userIdsForProject($project->id);

        $this->assertContains($viewer->id, $ids);
    }

    public function test_returns_empty_for_missing_project(): void
    {
        $ids = app(ProjectNotificationRecipientResolver::class)->userIdsForProject(0);

        $this->assertSame([], $ids);
    }
}
