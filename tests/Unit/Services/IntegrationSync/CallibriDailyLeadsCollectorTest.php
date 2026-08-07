<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Models\CallibriDailyLeadCount;
use App\Models\Project;
use App\Services\CallibriService;
use App\Services\IntegrationSync\Collectors\CallibriDailyLeadsCollector;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class CallibriDailyLeadsCollectorTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_collect_range_upserts_daily_counts_including_zero(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $credentials = Mockery::mock(IntegrationProjectCredentials::class);
        $credentials->shouldReceive('callibri')
            ->with($project->id)
            ->andReturn([
                'email' => 'a@b.c',
                'token' => 'tok',
                'site_id' => 1,
            ]);

        $callibri = Mockery::mock(CallibriService::class);
        $callibri->shouldReceive('getAndSaveLeadsByDay')
            ->once()
            ->withArgs(fn ($p, Carbon $day) => $p->id === $project->id && $day->toDateString() === '2026-08-04')
            ->andReturn(collect([['id' => 1], ['id' => 2]]));
        $callibri->shouldReceive('getAndSaveLeadsByDay')
            ->once()
            ->withArgs(fn ($p, Carbon $day) => $p->id === $project->id && $day->toDateString() === '2026-08-05')
            ->andReturn(collect());

        $collector = new CallibriDailyLeadsCollector($credentials, $callibri);
        $result = $collector->collectRange(
            $project->id,
            Carbon::parse('2026-08-04'),
            Carbon::parse('2026-08-05'),
        );

        $this->assertTrue($result->ok);
        $this->assertDatabaseHas('callibri_daily_lead_counts', [
            'project_id' => $project->id,
            'date' => '2026-08-04',
            'leads_count' => 2,
        ]);
        $this->assertDatabaseHas('callibri_daily_lead_counts', [
            'project_id' => $project->id,
            'date' => '2026-08-05',
            'leads_count' => 0,
        ]);
        $this->assertSame(2, CallibriDailyLeadCount::query()->where('project_id', $project->id)->count());
    }

    public function test_supports_project_requires_callibri_credentials(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $credentials = Mockery::mock(IntegrationProjectCredentials::class);
        $credentials->shouldReceive('callibri')->with($project->id)->andReturn(null);

        $collector = new CallibriDailyLeadsCollector(
            $credentials,
            Mockery::mock(CallibriService::class),
        );

        $this->assertFalse($collector->supportsProject($project->id));
    }
}
