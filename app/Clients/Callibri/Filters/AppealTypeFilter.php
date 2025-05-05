<?php

namespace App\Clients\Callibri\Filters;

use App\Clients\Callibri\Filters\Interfaces\FilterInterface;

class AppealTypeFilter implements FilterInterface
{
    public function __construct(
        private array $types,
        private string $appealsFilter
    ) {}

    public function apply(array $leads): array
    {
        return array_filter($leads, function($lead) {
            return in_array($lead['type'], $this->types)
                && $this->passesAppealFilter($lead);
        });
    }

    private function passesAppealFilter(array $lead): bool
    {
        return match($this->appealsFilter) {
            'first_only' => $lead['is_lid'] ?? false,
            default => true
        };
    }
}
