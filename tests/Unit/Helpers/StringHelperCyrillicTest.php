<?php

namespace Tests\Unit\Helpers;

use App\Clients\Callibri\Filters\LeadCostFilter;
use App\Helpers\StringHelper;
use Tests\TestCase;

class StringHelperCyrillicTest extends TestCase
{
    public function test_matches_cyrillic_class_name(): void
    {
        $this->assertTrue(StringHelper::matchesAnyPattern('Лид СА', ['Лид СА']));
    }

    public function test_empty_status_does_not_match_cyrillic_class(): void
    {
        $this->assertFalse(StringHelper::matchesAnyPattern('', ['Лид СА']));
    }

    public function test_lead_cost_filter_excludes_leads_with_empty_status(): void
    {
        $filter = new LeadCostFilter('selected_classes_only', 'Лид СА');

        $leads = [
            ['id' => 1, 'status' => ''],
            ['id' => 2, 'status' => 'Лид СА'],
        ];

        $filtered = array_values($filter->apply($leads));

        $this->assertCount(1, $filtered);
        $this->assertSame(2, $filtered[0]['id']);
    }

    public function test_lead_cost_filter_excludes_spam_class(): void
    {
        $filter = new LeadCostFilter('selected_classes_only', '!спам');

        $leads = [
            ['id' => 1, 'status' => 'спам'],
            ['id' => 2, 'status' => 'Лид СА'],
        ];

        $filtered = array_values($filter->apply($leads));

        $this->assertCount(1, $filtered);
        $this->assertSame(2, $filtered[0]['id']);
    }
}
