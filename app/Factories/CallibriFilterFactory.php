<?php

namespace App\Factories;

use App\Clients\Callibri\Filters\AppealTypeFilter;
use App\Clients\Callibri\Filters\LeadCostFilter;
use App\Clients\Callibri\Filters\UtmFilter;
use App\Models\Project;

class CallibriFilterFactory
{
    public function createFromSettings(array $settings): array
    {
        return [
            new UtmFilter(
                $settings['utm_source'] ?? null,
                $settings['utm_campaign'] ?? null,
                $settings['utm_filter_value'] ?? null
            ),
            new AppealTypeFilter(
                $settings['appeals_type'] ?? [],
                $settings['appeals_filter'] ?? 'all'
            ),
            new LeadCostFilter(
                $settings['lead_cost_calc'] ?? 'all',
                $settings['appeals_class'] ?? null
            )
        ];
    }
}
