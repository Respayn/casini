<?php

namespace Tests\Unit\Data\Channels;

use App\Data\Channels\ChannelReportQueryData;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChannelReportQueryDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_defaults_both_ends_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 15:00:00'));

        $data = ChannelReportQueryData::create();

        $this->assertSame('2026-08-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-08-01', $data->dateTo->toDateString());
        $this->assertTrue($data->isSingleMonthPeriod());
        $this->assertTrue(
            $data->columns->contains(fn ($column) => $column->field === 'project-type')
        );
        $this->assertFalse(
            $data->columns->contains(fn ($column) => $column->field === 'department')
        );
        $projectType = $data->columns->first(fn ($column) => $column->field === 'project-type');
        $this->assertSame('Тип клиенто-проекта', $projectType->label);
    }

    public function test_from_saved_settings_fills_missing_date_from(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $payload = ChannelReportQueryData::create()->toArray();
        unset($payload['dateFrom']);
        $payload['dateTo'] = '2026-07-01T00:00:00+00:00';

        $data = ChannelReportQueryData::hydrateFromSavedSettings($payload);

        $this->assertSame('2026-07-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-07-01', $data->dateTo->toDateString());
    }

    public function test_hydrate_refreshes_direct_budget_tooltip_from_canonical(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $payload = ChannelReportQueryData::create()->toArray();
        foreach ($payload['columns'] as &$column) {
            if (($column['field'] ?? null) === 'direct-budget') {
                $column['tooltip'] = 'старый текст — кликните на ячейку и данные обновятся';
            }
        }
        unset($column);

        $data = ChannelReportQueryData::hydrateFromSavedSettings($payload);
        $budget = $data->columns->first(fn ($column) => $column->field === 'direct-budget');

        $this->assertNotNull($budget);
        $this->assertStringContainsString('массовое действие', (string) $budget->tooltip);
        $this->assertStringNotContainsString('кликните', (string) $budget->tooltip);
    }

    public function test_hydrate_rebuilds_position_columns_and_preserves_visibility(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $rates = [
            (object) ['id' => 1, 'name' => 'Аналитик'],
            (object) ['id' => 2, 'name' => 'Дизайнер'],
        ];

        $saved = ChannelReportQueryData::create($rates);
        $saved->grouping = \App\Enums\ChannelReportGrouping::CLIENTS;
        $saved->showInactive = true;
        $saved->includeVat = true;

        $position = $saved->columns->first(fn ($column) => $column->field === 'position_1');
        $position->isVisible = false;
        $position->order = 0;

        $hydrated = ChannelReportQueryData::hydrateFromSavedSettings($saved->toJson(), $rates);

        $this->assertSame(\App\Enums\ChannelReportGrouping::CLIENTS, $hydrated->grouping);
        $this->assertTrue($hydrated->showInactive);
        $this->assertTrue($hydrated->includeVat);
        $this->assertTrue($hydrated->columns->contains(fn ($column) => $column->field === 'position_1'));
        $this->assertTrue($hydrated->columns->contains(fn ($column) => $column->field === 'position_2'));

        $hydratedPosition = $hydrated->columns->first(fn ($column) => $column->field === 'position_1');
        $this->assertFalse($hydratedPosition->isVisible);
        $this->assertSame(0, $hydratedPosition->order);
    }

    public function test_apply_saved_prefs_keeps_trailing_columns_after_positions_when_schema_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $savedRates = [(object) ['id' => 1, 'name' => 'Аналитик']];
        $saved = ChannelReportQueryData::create($savedRates);

        $rebuilt = ChannelReportQueryData::create([
            (object) ['id' => 1, 'name' => 'Аналитик'],
            (object) ['id' => 2, 'name' => 'Дизайнер'],
        ]);
        $rebuilt->applySavedColumnPreferences($saved->columns);

        $visibleFields = $rebuilt->columns
            ->filter(fn ($column) => $column->isVisible)
            ->pluck('field')
            ->values()
            ->all();

        $lastPositionIdx = array_search('position_2', $visibleFields, true);
        $summaryIdx = array_search('summary-spendings', $visibleFields, true);

        $this->assertNotFalse($lastPositionIdx);
        $this->assertNotFalse($summaryIdx);
        $this->assertGreaterThan($lastPositionIdx, $summaryIdx);
        $this->assertSame(
            ['summary-spendings', 'direct-budget', 'direct-spendings'],
            array_slice($visibleFields, -3)
        );
    }

    public function test_clamp_period_blocks_future_and_swaps_inverted_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = ChannelReportQueryData::create();
        $data->dateFrom = Carbon::parse('2026-10-01');
        $data->dateTo = Carbon::parse('2026-09-01');
        $data->clampPeriodToPresent();

        $this->assertSame('2026-08-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-08-01', $data->dateTo->toDateString());
    }

    public function test_is_single_month_period_false_for_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = ChannelReportQueryData::create();
        $data->dateFrom = Carbon::parse('2026-06-01');
        $data->dateTo = Carbon::parse('2026-08-01');

        $this->assertFalse($data->isSingleMonthPeriod());
    }
}
