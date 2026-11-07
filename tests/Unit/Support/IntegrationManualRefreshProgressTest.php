<?php

namespace Tests\Unit\Support;

use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IntegrationManualRefreshProgressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_source_labels_are_short(): void
    {
        $labels = IntegrationManualRefreshProgress::sourceLabels();

        $this->assertSame('Директ: расход', $labels['yandex_direct_daily_spend']);
        $this->assertSame('Callibri: лиды', $labels['callibri_daily_leads']);
        $this->assertSame('Бонусы', IntegrationManualRefreshProgress::labelForSource('bonuses'));
    }

    public function test_progress_is_keyed_by_agency_and_stores_project_name(): void
    {
        $progress = new IntegrationManualRefreshProgress;

        $progress->start(7, 'channels', [
            ['id' => 10, 'label' => 'Проект А', 'group' => null],
            ['id' => 20, 'label' => 'Проект Б', 'group' => 'Клиент'],
        ], '2026-10-01', '2026-10-01', false);

        $this->assertNull($progress->get(3));
        $this->assertTrue($progress->isRunning(7));

        $progress->setCurrentStep(7, 10, 'yandex_direct_daily_spend', 1);
        $state = $progress->get(7);

        $this->assertSame(IntegrationManualRefreshProgress::STATUS_RUNNING, $state['status']);
        $this->assertSame(7, $state['agency_id']);
        $this->assertSame('Директ: расход', $state['current_label']);
        $this->assertSame('Проект А', $state['current_project_label']);
        $this->assertSame(1, $state['current_index']);
        $this->assertSame('2026-10-01', $state['period_from']);
        $this->assertSame('2026-10-01', $state['period_to']);
        $this->assertFalse($state['include_vat']);
        $this->assertCount(2, $state['pending']);

        $after = $progress->markProjectFinished(7, 10, 'updated');

        $this->assertCount(1, $after['pending']);
        $this->assertSame(20, $after['pending'][0]['id']);
        $this->assertSame(1, $after['updated']);
        $this->assertSame([10, 20], $after['all_ids']);
        $this->assertNull($after['current_project_label']);

        $progress->markProjectFinished(7, 20, 'failed');
        $done = $progress->markDone(7);

        $this->assertSame(IntegrationManualRefreshProgress::STATUS_DONE, $done['status']);
        $this->assertSame([], $done['pending']);
        $this->assertSame(1, $done['failed']);
        $this->assertSame(
            'Данные в Каналах и Статистике обновлены. Ошибок: 1',
            IntegrationManualRefreshProgress::sharedFinishMessage(1),
        );
    }

    public function test_pending_in_slice_requires_same_period_and_vat(): void
    {
        $progress = new IntegrationManualRefreshProgress;

        $state = $progress->start(3, 'statistics', [
            ['id' => 100, 'label' => 'А', 'group' => null],
            ['id' => 200, 'label' => 'Б', 'group' => null],
        ], '2026-10-01', '2026-10-01', true);

        $this->assertTrue(IntegrationManualRefreshProgress::isProjectPendingInSlice(
            $state,
            100,
            '2026-10-01',
            '2026-10-01',
            true,
        ));

        $this->assertFalse(IntegrationManualRefreshProgress::isProjectPendingInSlice(
            $state,
            100,
            '2026-09-01',
            '2026-09-01',
            true,
        ));

        $this->assertFalse(IntegrationManualRefreshProgress::isProjectPendingInSlice(
            $state,
            100,
            '2026-10-01',
            '2026-10-01',
            false,
        ));

        $after = $progress->markProjectFinished(3, 100, 'updated');

        $this->assertFalse(IntegrationManualRefreshProgress::isProjectPendingInSlice(
            $after,
            100,
            '2026-10-01',
            '2026-10-01',
            true,
        ));

        $this->assertTrue(IntegrationManualRefreshProgress::isProjectPendingInSlice(
            $after,
            200,
            '2026-10-01',
            '2026-10-01',
            true,
        ));
    }

    public function test_noticed_is_tracked_per_user_and_product(): void
    {
        $progress = new IntegrationManualRefreshProgress;

        $progress->start(5, 'channels', [
            ['id' => 1, 'label' => 'А', 'group' => null],
        ], '2026-10-01', '2026-10-01', false);
        $progress->markDone(5);

        $state = $progress->get(5);
        $this->assertFalse($progress->wasNoticed($state, 1, 'channels'));
        $this->assertFalse($progress->wasNoticed($state, 1, 'statistics'));

        $progress->markNoticed(5, 1, 'channels');
        $state = $progress->get(5);

        $this->assertTrue($progress->wasNoticed($state, 1, 'channels'));
        $this->assertFalse($progress->wasNoticed($state, 1, 'statistics'));
        $this->assertFalse($progress->wasNoticed($state, 2, 'channels'));
    }

    public function test_bonuses_step_clears_project_name(): void
    {
        $progress = new IntegrationManualRefreshProgress;

        $progress->start(9, 'statistics', [
            ['id' => 11, 'label' => 'Атлант групп', 'group' => null],
        ], '2026-10-01', '2026-10-01', false);

        $progress->setCurrentStep(9, 11, 'bitrix24_labor', 1);
        $this->assertSame('Атлант групп', $progress->get(9)['current_project_label']);

        $progress->setCurrentStep(9, 11, 'bonuses', 1);
        $state = $progress->get(9);

        $this->assertSame('Бонусы', $state['current_label']);
        $this->assertNull($state['current_project_label']);
    }
}
