<?php

namespace App\Factories;

use App\Clients\Callibri\Filters\AppealTypeFilter;
use App\Clients\Callibri\Filters\LeadCostFilter;
use App\Clients\Callibri\Filters\UtmFilter;

class CallibriFilterFactory
{
    public function createFromSettings(array $settings): array
    {
        $utmMode = $settings['utm_filter_mode'] ?? 'none';

        $filters = [];

        if ($utmMode !== 'none') {
            $filters[] = new UtmFilter(
                $utmMode,
                $settings['utm_source'] ?? null,
                $settings['utm_campaign'] ?? null,
                $settings['utm_medium'] ?? null,
            );
        }

        $filters[] = new AppealTypeFilter(
            $settings['appeals_type'] ?? [],
            $settings['appeals_filter'] ?? 'all'
        );

        $filters[] = new LeadCostFilter(
            $settings['lead_cost_calc'] ?? 'all',
            $settings['appeals_class'] ?? null
        );

        return $filters;
    }
}
