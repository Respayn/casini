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

    public function test_progress_moves_project_from_pending_to_finished(): void
    {
        $progress = new IntegrationManualRefreshProgress;

        $progress->start(7, 'channels', [
            ['id' => 10, 'label' => 'Проект А', 'group' => null],
            ['id' => 20, 'label' => 'Проект Б', 'group' => 'Клиент'],
        ]);

        $progress->setCurrentStep(7, 'channels', 10, 'yandex_direct_daily_spend', 1);
        $state = $progress->get(7, 'channels');

        $this->assertSame(IntegrationManualRefreshProgress::STATUS_RUNNING, $state['status']);
        $this->assertSame('Директ: расход', $state['current_label']);
        $this->assertSame(1, $state['current_index']);
        $this->assertCount(2, $state['pending']);

        $after = $progress->markProjectFinished(7, 'channels', 10, 'updated');

        $this->assertCount(1, $after['pending']);
        $this->assertSame(20, $after['pending'][0]['id']);
        $this->assertSame(1, $after['updated']);
        $this->assertSame([10, 20], $after['all_ids']);

        $progress->markProjectFinished(7, 'channels', 20, 'failed');
        $done = $progress->markDone(7, 'channels');

        $this->assertSame(IntegrationManualRefreshProgress::STATUS_DONE, $done['status']);
        $this->assertSame([], $done['pending']);
        $this->assertSame(1, $done['failed']);
    }
}
