<?php

namespace App\Services;

use App\Clients\Callibri\CallibriClient;
use App\Clients\Callibri\Filters\Interfaces\FilterInterface;
use App\Data\Callibri\SiteData;
use App\Exceptions\CallibriApiException;
use App\Factories\CallibriClientFactory;
use App\Factories\CallibriFilterFactory;
use App\Models\CallibriLead;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Repositories\Interfaces\CallibriLeadRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

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

    private function applyFilters(array $statistics, array $filters): array
    {
        $leads = array_merge(...array_map(
            fn($channel) => $channel['leads'] ?? [],
            $statistics
        ));

        return array_reduce(
            $filters,
            fn($carry, FilterInterface $filter) => $filter->apply($carry),
            $leads
        );
    }


    public function getSites(): Collection
    {
        try {
            $response = $this->client->request('GET', 'get_sites');

            return collect($response['sites'])->map(
                fn(array $item) => SiteData::from($item)
            );

        } catch (\Exception $e) {
            throw new CallibriApiException('Failed to get sites', 0, $e);
        }
    }

    public function getLeads(
        Project $project,
        Carbon $start,
        Carbon $end,
        bool $withSave = false
    ): Collection {
        $this->setupClientForProject($project);

        $leads = collect();
        $filters = $this->filterFactory->createFromSettings($this->integration->settings);

        foreach ($this->createDateRanges($start, $end) as [$periodStart, $periodEnd]) {
            $response = $this->client->request('GET', 'site_get_statistics', [
                'query' => [
                    'site_id' => $this->integration->settings['site_id'],
                    'date1' => $periodStart->format('d.m.Y'),
                    'date2' => $periodEnd->format('d.m.Y')
                ]
            ]);

            $filtered = $this->applyFilters($response['channels_statistics'] ?? [], $filters);

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
