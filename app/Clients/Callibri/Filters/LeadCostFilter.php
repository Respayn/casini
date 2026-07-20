<?php

namespace App\Clients\Callibri\Filters;

use App\Clients\Callibri\Filters\Interfaces\FilterInterface;
use App\Helpers\StringHelper;

class LeadCostFilter implements FilterInterface
{
    public function __construct(
        private string $calculationMethod,
        private ?string $appealClasses = null
    ) {}

    public function apply(array $leads): array
    {
        if ($this->calculationMethod !== 'selected_classes_only') {
            return $leads;
        }

        return array_filter($leads, function ($lead) {
            return $this->matchesAppealClasses($lead['status'] ?? '');
        });
    }

    private function matchesAppealClasses(?string $status): bool
    {
        $patterns = array_filter(array_map('trim', explode(',', $this->appealClasses ?? '')));

        if (empty($patterns)) {
            return trim($status ?? '') !== '';
        }

        $inclusions = [];
        $exclusions = [];

        foreach ($patterns as $pattern) {
            if (str_starts_with($pattern, '!')) {
                $exclusions[] = substr($pattern, 1);
            } else {
                $inclusions[] = $pattern;
            }
        }

        if (StringHelper::matchesAnyPattern($status ?? '', $exclusions)) {
            return false;
        }

        if (empty($inclusions)) {
            return true;
        }

        return StringHelper::matchesAnyPattern($status ?? '', $inclusions);
    }
}
