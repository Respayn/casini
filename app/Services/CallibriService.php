<?php

namespace App\Services;

use App\Clients\Callibri\CallibriClient;
use App\Clients\Callibri\Filters\Interfaces\FilterInterface;
use App\Data\Callibri\SiteData;
use App\Exceptions\CallibriApiException;
use App\Factories\CallibriClientFactory;
use App\Factories\CallibriFilterFactory;
use App\Models\Agency;
use App\Models\CallibriLead;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Repositories\Interfaces\CallibriLeadRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CallibriService
{
    private const API_RATE_LIMIT_DELAY = 1;
    private CallibriClient $client;
    private IntegrationProject $integration;

    public function __construct(
        private CallibriClientFactory $clientFactory,
        private CallibriFilterFactory $filterFactory,
        private IntegrationRepositoryInterface $integrationRepository,
        private CallibriLeadRepositoryInterface $leadRepository,
    ) {}

    private function getCallibriIntegration(Project $project): IntegrationProject
    {
        return $this->integrationRepository->getActiveCallibriIntegration($project->id);
    }

    public function setupClient(string $email, string $token): void
    {
        $this->client = $this->clientFactory->create($email, $token);
    }

    public function setupClientForProject(Project $project): void
    {
        $this->integration = $this->getCallibriIntegration($project);

        $requiredParams = [
            'email' => $this->integration->settings['email'] ?? null,
            'token' => $this->integration->settings['token'] ?? null,
            'site_id' => $this->integration->settings['site_id'] ?? null
        ];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                throw new \Exception(
                    "Missing required Callibri parameter: $param for project {$project->id}"
                );
            }
        }

        $this->client = $this->clientFactory->create(
            $requiredParams['email'],
            $requiredParams['token'],
        );
    }

    private function extractLeadsFromStatistics(array $channelsStatistics): array
    {
        $typeMapping = [
            'calls' => 'calls',
            'chats' => 'chats',
            'emails' => 'emails',
            'feedbacks' => 'requests',
        ];

        $leads = [];

        foreach ($channelsStatistics as $channel) {
            foreach ($typeMapping as $apiKey => $type) {
                foreach ($channel[$apiKey] ?? [] as $lead) {
                    $lead['type'] = $type;
                    $leads[] = $lead;
                }
            }
        }

        return $leads;
    }

    private function applyFilters(
        array $statistics,
        array $filters,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $timezone
    ): array {
        $leads = $this->extractLeadsFromStatistics($statistics);
        $leads = $this->filterLeadsByLocalDate($leads, $dateFrom, $dateTo, $timezone);

        if ($leads === []) {
            return [];
        }

        return array_values(array_reduce(
            $filters,
            fn ($carry, FilterInterface $filter) => $filter->apply($carry),
            $leads
        ));
    }

    private function resolveTimezone(?string $timezone = null): string
    {
        if ($timezone !== null && $timezone !== '') {
            return $timezone;
        }

        $agencyId = session('current_agency_id') ?? Auth::user()?->agencies()->first()?->id;

        if ($agencyId) {
            $agencyTimezone = Agency::query()->whereKey($agencyId)->value('time_zone');

            if ($agencyTimezone) {
                return $agencyTimezone;
            }
        }

        return config('app.timezone', 'UTC');
    }

    private function parseLeadDateUtc(array $lead): ?Carbon
    {
        if (empty($lead['date'])) {
            return null;
        }

        try {
            return Carbon::parse($lead['date'])->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $leads
     * @return array<int, array<string, mixed>>
     */
    private function filterLeadsByLocalDate(
        array $leads,
        Carbon $from,
        Carbon $to,
        string $timezone
    ): array {
        $fromDay = $from->copy()->timezone($timezone)->startOfDay();
        $toDay = $to->copy()->timezone($timezone)->endOfDay();

        return array_values(array_filter(
            $leads,
            function (array $lead) use ($fromDay, $toDay, $timezone) {
                $leadDate = $this->parseLeadDateUtc($lead);

                if ($leadDate === null) {
                    return false;
                }

                return $leadDate->copy()->timezone($timezone)->betweenIncluded($fromDay, $toDay);
            }
        ));
    }

    public function getSites(): Collection
    {
        try {
            $response = $this->client->request('GET', 'get_sites');

            if (! is_array($response)) {
                throw new CallibriApiException('Invalid response from Callibri API');
            }

            if (isset($response['error'])) {
                throw new CallibriApiException((string) $response['error']);
            }

            if (! isset($response['sites']) || ! is_array($response['sites'])) {
                throw new CallibriApiException('Invalid response from Callibri API');
            }

            return collect($response['sites'])->map(
                fn (array $item) => SiteData::from($item)
            );

        } catch (CallibriApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CallibriApiException('Failed to get sites', 0, $e);
        }
    }

    public function listSites(string $email, string $token, ?int $includeSiteId = null): Collection
    {
        $this->setupClient($email, $token);

        try {
            $response = $this->client->request('GET', 'get_sites');

            if (! is_array($response)) {
                throw new CallibriApiException('Invalid response from Callibri API');
            }

            if (isset($response['error'])) {
                throw new CallibriApiException((string) $response['error']);
            }

            if (! isset($response['sites']) || ! is_array($response['sites'])) {
                throw new CallibriApiException('Invalid response from Callibri API');
            }

            $allSites = collect($response['sites'])
                ->map(fn (array $item) => [
                    'id' => (int) ($item['site_id'] ?? 0),
                    'label' => sprintf(
                        '%s (%s)',
                        $item['sitename'] ?? 'Без названия',
                        $item['site_id'] ?? ''
                    ),
                    'isActive' => filter_var($item['active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ])
                ->filter(fn (array $site) => $site['id'] > 0);

            $sites = $allSites->filter(fn (array $site) => $site['isActive']);

            if ($includeSiteId !== null) {
                $includedSite = $allSites->first(fn (array $site) => $site['id'] === $includeSiteId);

                if ($includedSite !== null && ! $sites->contains(fn (array $site) => $site['id'] === $includeSiteId)) {
                    $sites = $sites->push($includedSite);
                }
            }

            return $sites
                ->map(fn (array $site) => [
                    'id' => $site['id'],
                    'label' => $site['label'],
                ])
                ->values();
        } catch (CallibriApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CallibriApiException('Failed to get sites', 0, $e);
        }
    }

    public function countLeadsForDate(array $settings, Carbon $date, ?string $timezone = null): int
    {
        $this->setupClient($settings['email'], $settings['token']);

        $timezone = $this->resolveTimezone($timezone);
        $filters = $this->filterFactory->createFromSettings($settings);

        $response = $this->client->request('GET', 'site_get_statistics', [
            'query' => [
                'site_id' => $settings['site_id'],
                'date1' => $date->copy()->subDay()->format('d.m.Y'),
                'date2' => $date->format('d.m.Y'),
            ],
        ]);

        $filtered = $this->applyFilters(
            $response['channels_statistics'] ?? [],
            $filters,
            $date,
            $date,
            $timezone
        );

        return count($filtered);
    }

    public function getLeads(
        Project $project,
        Carbon $start,
        Carbon $end,
        bool $withSave = false
    ): Collection {
        $this->setupClientForProject($project);

        $timezone = $this->resolveTimezone();
        $leads = collect();
        $filters = $this->filterFactory->createFromSettings($this->integration->settings);

        foreach ($this->createDateRanges($start, $end) as [$periodStart, $periodEnd]) {
            $response = $this->client->request('GET', 'site_get_statistics', [
                'query' => [
                    'site_id' => $this->integration->settings['site_id'],
                    'date1' => $periodStart->copy()->subDay()->format('d.m.Y'),
                    'date2' => $periodEnd->format('d.m.Y'),
                ],
            ]);

            $filtered = $this->applyFilters(
                $response['channels_statistics'] ?? [],
                $filters,
                $periodStart,
                $periodEnd,
                $timezone
            );

            $leads = $leads->merge($filtered);

            sleep(self::API_RATE_LIMIT_DELAY);
        }

        if ($withSave) {
            $this->saveLeads($leads, $project['id']);
        }

        return $leads;
    }

    public function getLeadsByDay(Project $project, Carbon $date): Collection
    {
        return $this->getLeads($project, $date->copy()->startOfDay(), $date->copy()->endOfDay());
    }

    public function getLeadsByMonth(Project $project, Carbon $date): Collection
    {
        return $this->getLeads(
            $project,
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth()
        );
    }

    public function getAndSaveLeadsByDay(Project $project, Carbon $date): Collection
    {
        return $this->getLeads(
            $project,
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
            true
        );
    }

    private function createDateRanges(Carbon $start, Carbon $end): iterable
    {
        if ($start->eq($end)) {
            yield [$start, $end];
            return;
        }

        $period = CarbonPeriod::create($start, '1 week', $end);

        foreach ($period as $weekStart) {
            $weekEnd = min($weekStart->copy()->addWeek()->subDay(), $end);
            yield [$weekStart, $weekEnd];
        }
    }

    private function isDuplicateLead(int $projectId, string $externalId): bool
    {
        return CallibriLead::where([
            'project_id' => $projectId,
            'external_id' => $externalId
        ])->exists();
    }

    private function saveLeads(Collection $leads, int $projectId): void
    {
        $leads->each(function($lead) use ($projectId) {
            if (!$this->leadRepository->isDuplicate($projectId, $lead['id'])) {
                $this->leadRepository->saveLead($lead, $projectId);
            }
        });
    }
}
