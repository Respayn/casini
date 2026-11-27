<?php

namespace Tests\Unit\Support;

use App\Enums\ChannelReportGrouping;
use App\Support\SharedProductFilters;
use Tests\TestCase;

class SharedProductFiltersGroupingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session()->forget(SharedProductFilters::GROUPING_KEY);
    }

    public function test_resolve_grouping_remembers_page_value_when_session_empty(): void
    {
        $resolved = SharedProductFilters::resolveGrouping(ChannelReportGrouping::CLIENTS->value);

        $this->assertSame(ChannelReportGrouping::CLIENTS->value, $resolved);
        $this->assertSame(
            ChannelReportGrouping::CLIENTS->value,
            session(SharedProductFilters::GROUPING_KEY),
        );
    }

    public function test_resolve_grouping_prefers_session_over_page_value(): void
    {
        session([SharedProductFilters::GROUPING_KEY => ChannelReportGrouping::PROJECT_TYPE->value]);

        $resolved = SharedProductFilters::resolveGrouping(ChannelReportGrouping::NONE->value);

        $this->assertSame(ChannelReportGrouping::PROJECT_TYPE->value, $resolved);
    }

    public function test_remember_grouping_normalizes_unknown_values(): void
    {
        SharedProductFilters::rememberGrouping('something-else');

        $this->assertSame(
            ChannelReportGrouping::NONE->value,
            session(SharedProductFilters::GROUPING_KEY),
        );
    }

    public function test_pinned_column_field_for_products(): void
    {
        $this->assertSame(
            'client',
            SharedProductFilters::pinnedColumnField(ChannelReportGrouping::CLIENTS->value),
        );
        $this->assertSame(
            'project-type',
            SharedProductFilters::pinnedColumnField(ChannelReportGrouping::PROJECT_TYPE->value),
        );
        $this->assertSame(
            'channel',
            SharedProductFilters::pinnedColumnField(
                ChannelReportGrouping::PROJECT_TYPE->value,
                projectTypeField: 'channel',
            ),
        );
        $this->assertNull(
            SharedProductFilters::pinnedColumnField(ChannelReportGrouping::NONE->value),
        );
    }
}
