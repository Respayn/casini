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

        return array_filter($leads, function($lead) {
            return $this->matchesAppealClasses($lead['status'] ?? '');
        });
    }

    private function matchesAppealClasses(?string $status): bool
    {
        $classes = explode(',', $this->appealClasses ?? '');
        return StringHelper::matchesAnyPattern(
            $status ?? '',
            $classes
        );
    }
}
