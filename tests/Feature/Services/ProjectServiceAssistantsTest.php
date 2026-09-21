<?php

namespace Tests\Feature\Services;

use App\Data\ProjectData;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ProjectServiceAssistantsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_or_create_project_syncs_assistants(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);
        $firstAssistant = User::factory()->create(['is_active' => true]);
        $secondAssistant = User::factory()->create(['is_active' => true]);

        $service = app(ProjectService::class);
        $data = $service->getProjectDataById($project->id);

        $updated = $service->updateOrCreateProject(ProjectData::from([
            ...$data->toArray(),
            'assistantIds' => [$firstAssistant->id, $secondAssistant->id],
        ]));

        $this->assertSame(
            [$firstAssistant->id, $secondAssistant->id],
            $updated->assistantIds
        );
        $this->assertEqualsCanonicalizing(
            [$firstAssistant->id, $secondAssistant->id],
            $project->fresh()->assistants->pluck('id')->all()
        );

        $cleared = $service->updateOrCreateProject(ProjectData::from([
            ...$updated->toArray(),
            'assistantIds' => [],
        ]));

        $this->assertSame([], $cleared->assistantIds);
        $this->assertCount(0, $project->fresh()->assistants);
    }
}
