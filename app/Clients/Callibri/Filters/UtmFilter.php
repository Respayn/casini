<?php

namespace App\Clients\Callibri\Filters;

use App\Clients\Callibri\Filters\Interfaces\FilterInterface;
use App\Helpers\StringHelper;

class UtmFilter implements FilterInterface
{
    public function __construct(
        private string $mode = 'none',
        private ?string $source = null,
        private ?string $campaign = null,
        private ?string $medium = null
    ) {}

    public function apply(array $leads): array
    {
        if ($this->mode === 'none') {
            return $leads;
        }

        return array_filter($leads, function ($lead) {
            return $this->matchesSource($lead)
                && $this->matchesCampaign($lead)
                && $this->matchesMedium($lead);
        });
    }

    private function matchesSource(array $lead): bool
    {
        return $this->matchField($lead['utm_source'] ?? null, $this->source, $this->mode === 'source');
    }

    private function matchesCampaign(array $lead): bool
    {
        return $this->matchField($lead['utm_campaign'] ?? null, $this->campaign, $this->mode === 'campaign');
    }

    private function matchesMedium(array $lead): bool
    {
        return $this->matchField($lead['utm_medium'] ?? null, $this->medium, $this->mode === 'medium');
    }

    private function matchField(?string $value, ?string $pattern, bool $fieldActive): bool
    {
        if (! $fieldActive) {
            return true;
        }

        if ($pattern === null || trim($pattern) === '') {
            return trim($value ?? '') !== '';
        }

        return StringHelper::containsAnyNormalized(
            $value ?? '',
            array_map('trim', explode(',', $pattern))
        );
    }
}
