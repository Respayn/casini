<?php

namespace Tests\Unit\Domain\Statistics;

use App\Domain\Statistics\Services\StatisticsMonthlyBonusSnapshotService;
use App\Models\Project;
use App\Models\StatisticsProjectMonthlyBonus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatisticsMonthlyBonusSnapshotServiceTest extends TestCase
{
    use DatabaseTransactions;

    private StatisticsMonthlyBonusSnapshotService $service;

    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatisticsMonthlyBonusSnapshotService;

        $project = Project::query()->first();
        if ($project === null) {
            $this->markTestSkipped('Нужен хотя бы один проект в БД для FK снимка бонусов');
        }
        $this->projectId = (int) $project->id;

        StatisticsProjectMonthlyBonus::query()
            ->where('project_id', $this->projectId)
            ->where('year', 2026)
            ->where('month', 7)
            ->delete();
    }

    public function test_resolve_saves_first_snapshot(): void
    {
        $month = Carbon::parse('2026-07-01');
        $result = $this->service->resolve(
            $this->projectId,
            $month,
            ['kind' => 'amount', 'value' => 15000.0],
        );

        $this->assertSame('amount', $result['kind']);
        $this->assertSame(15000.0, $result['value']);

        $row = StatisticsProjectMonthlyBonus::query()
            ->where('project_id', $this->projectId)
            ->where('year', 2026)
            ->where('month', 7)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(15000.0, (float) $row->value);
    }

    public function test_resolve_returns_existing_snapshot_without_overwrite(): void
    {
        $month = Carbon::parse('2026-07-01');
        $this->service->upsert($this->projectId, 2026, 7, [
            'kind' => 'amount',
            'value' => 5000.0,
        ]);

        $result = $this->service->resolve(
            $this->projectId,
            $month,
            ['kind' => 'amount', 'value' => 99999.0],
            forceRecalculate: false,
        );

        $this->assertSame(5000.0, $result['value']);
    }

    public function test_resolve_force_overwrites_snapshot(): void
    {
        $month = Carbon::parse('2026-07-01');
        $this->service->upsert($this->projectId, 2026, 7, [
            'kind' => 'not_configured',
        ]);

        $result = $this->service->resolve(
            $this->projectId,
            $month,
            ['kind' => 'amount', 'value' => -3000.0],
            forceRecalculate: true,
        );

        $this->assertSame('amount', $result['kind']);
        $this->assertSame(-3000.0, $result['value']);

        $row = StatisticsProjectMonthlyBonus::query()
            ->where('project_id', $this->projectId)
            ->where('year', 2026)
            ->where('month', 7)
            ->first();

        $this->assertSame('amount', $row->kind);
        $this->assertSame(-3000.0, (float) $row->value);
    }

    public function test_resolve_does_not_persist_dash(): void
    {
        $month = Carbon::parse('2026-07-01');
        $result = $this->service->resolve(
            $this->projectId,
            $month,
            ['kind' => 'dash'],
        );

        $this->assertSame('dash', $result['kind']);
        $this->assertNull(
            StatisticsProjectMonthlyBonus::query()
                ->where('project_id', $this->projectId)
                ->where('year', 2026)
                ->where('month', 7)
                ->first()
        );
    }
}
