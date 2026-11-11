<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Models\CallibriDailyLeadCount;
use App\Models\CallibriLead;
use App\Models\Client;
use App\Models\Project;
use App\Services\CallibriService;
use App\Services\IntegrationSync\Collectors\CallibriDailyLeadsCollector;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\ProjectFacts\ProjectKpiFactsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CallibriDailyLeadsCollectorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('callibri_daily_lead_counts') || ! Schema::hasTable('projects')) {
            $this->markTestSkipped('Нет таблиц Callibri в локальной БД (нужна MariaDB как на staging)');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeActiveProject(): Project
    {
        $client = Client::factory()->create(['manager_id' => null]);
        $departmentId = Schema::hasTable('departments')
            ? \DB::table('departments')->value('id')
            : null;

        return Project::factory()->create(array_filter([
            'client_id' => $client->id,
            'specialist_id' => null,
            'is_active' => true,
            'department_id' => $departmentId,
        ], static fn ($v) => $v !== null));
    }

    public function test_collect_range_upserts_daily_counts_including_zero(): void
    {
        $project = $this->makeActiveProject();

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

        $facts = Mockery::mock(ProjectKpiFactsService::class);
        $facts->shouldReceive('filteredCallibriLeads')
            ->once()
            ->withArgs(fn (int $id, Carbon $from, Carbon $to) => $id === $project->id
                && $from->toDateString() === '2026-08-04'
                && $to->toDateString() === '2026-08-04')
            ->andReturn([new CallibriLead, new CallibriLead]);
        $facts->shouldReceive('filteredCallibriLeads')
            ->once()
            ->withArgs(fn (int $id, Carbon $from, Carbon $to) => $id === $project->id
                && $from->toDateString() === '2026-08-05'
                && $to->toDateString() === '2026-08-05')
            ->andReturn([]);

        $collector = new CallibriDailyLeadsCollector($credentials, $callibri, $facts);
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

    public function test_api_failure_does_not_overwrite_daily_count_with_zero(): void
    {
        $project = $this->makeActiveProject();

        CallibriDailyLeadCount::query()->create([
            'project_id' => $project->id,
            'date' => '2026-08-04',
            'leads_count' => 7,
        ]);

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
            ->andThrow(new \RuntimeException('API down'));

        $facts = Mockery::mock(ProjectKpiFactsService::class);
        $facts->shouldReceive('filteredCallibriLeads')->never();

        $collector = new CallibriDailyLeadsCollector($credentials, $callibri, $facts);
        $result = $collector->collectRange(
            $project->id,
            Carbon::parse('2026-08-04'),
            Carbon::parse('2026-08-04'),
        );

        $this->assertTrue($result->ok);
        $this->assertDatabaseHas('callibri_daily_lead_counts', [
            'project_id' => $project->id,
            'date' => '2026-08-04',
            'leads_count' => 7,
        ]);
    }

    public function test_supports_project_requires_callibri_credentials(): void
    {
        $project = $this->makeActiveProject();

        $credentials = Mockery::mock(IntegrationProjectCredentials::class);
        $credentials->shouldReceive('callibri')->with($project->id)->andReturn(null);

        $collector = new CallibriDailyLeadsCollector(
            $credentials,
            Mockery::mock(CallibriService::class),
            Mockery::mock(ProjectKpiFactsService::class),
        );

        $this->assertFalse($collector->supportsProject($project->id));
    }
}
