<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Models\Project;
use App\Services\Bitrix24\Bitrix24ApiException;
use App\Services\Bitrix24\Bitrix24Client;
use App\Services\Bitrix24\Bitrix24TaskSelection;
use App\Services\Bitrix24\Bitrix24WorkParser;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Support\SafeLogger;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Src\Domain\CompletedWorks\CompletedWorkRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;
use Throwable;

class Bitrix24WorksCollector implements IntegrationSyncCollector
{
    public const KEY = 'bitrix24_works';

    public const INTEGRATION_CODE = 'bitrix24';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly Bitrix24Client $client,
        private readonly CompletedWorkRepositoryInterface $completedWorks,
    ) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function integrationCode(): string
    {
        return self::INTEGRATION_CODE;
    }

    public function supportsProject(int $projectId): bool
    {
        if (! Project::query()->whereKey($projectId)->where('is_active', true)->exists()) {
            return false;
        }

        return $this->credentials->bitrix24($projectId) !== null;
    }

    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
    {
        return $this->collectRange(
            $context->projectId,
            $context->targetDate->copy()->startOfDay(),
            $context->targetDate->copy()->startOfDay(),
        );
    }

    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        $settings = $this->credentials->bitrix24($projectId);

        if ($settings === null) {
            return IntegrationSyncResult::failure('Нет настроенной интеграции Битрикс24', requeue: false);
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        try {
            $timezone = $settings['timezone'];
            $monthStart = Carbon::parse($fromDate, $timezone)->startOfMonth()->startOfDay();
            $monthEnd = Carbon::parse($toDate, $timezone)->endOfMonth()->endOfDay();
            $works = $this->fetchWorks($settings, $monthStart->toDateString(), $monthEnd->toDateString());
            $period = new DateTimeRange(
                DateTimeImmutable::createFromInterface($monthStart),
                DateTimeImmutable::createFromInterface($monthEnd),
            );

            $this->completedWorks->replaceForPeriod($projectId, $period, $works);

            return IntegrationSyncResult::success();
        } catch (Bitrix24ApiException $e) {
            return $this->failure($projectId, $fromDate, $toDate, $e->getMessage());
        } catch (Throwable $e) {
            return $this->failure($projectId, $fromDate, $toDate, SafeLogger::publicMessage($e));
        }
    }

    /**
     * @param  array{webhook: string, root_task_id: int, search_query: string, timezone: string, parse_comment_works: bool}  $settings
     * @return list<array{title: string, completed_at: DateTimeImmutable}>
     */
    private function fetchWorks(array $settings, string $fromDate, string $toDate): array
    {
        $timezone = $settings['timezone'];
        $periodStart = Carbon::parse($fromDate, $timezone)->startOfDay();
        $periodEnd = Carbon::parse($toDate, $timezone)->endOfDay();
        $fromMonth = $periodStart->copy()->startOfMonth();
        $toMonth = $periodEnd->copy()->startOfMonth();

        $monthTasks = array_values(array_filter(
            $this->client->descendantTasks($settings['webhook'], $settings['root_task_id']),
            function (array $task) use ($settings, $periodStart, $fromMonth, $toMonth): bool {
                if (! Bitrix24TaskSelection::titleMatches($task['title'], $settings['search_query'])) {
                    return false;
                }

                if (! Bitrix24TaskSelection::hasMonthInTitle($task['title'])) {
                    return false;
                }

                $taskMonth = Bitrix24TaskSelection::reportMonth($task['title'], $periodStart)->startOfDay();

                return ! $taskMonth->lt($fromMonth) && ! $taskMonth->gt($toMonth);
            },
        ));

        $works = [];
        $seen = [];

        foreach ($monthTasks as $task) {
            foreach ($this->client->taskChecklist($settings['webhook'], $task['id']) as $item) {
                if (! $item['is_complete']) {
                    continue;
                }

                $day = $item['toggled_at']?->copy()->timezone($timezone)->toDateString();

                if ($day === null || $day < $fromDate || $day > $toDate) {
                    $day = $fromDate;
                }

                $this->pushWork($works, $seen, $item['title'], $day);
            }

            if (! $settings['parse_comment_works']) {
                continue;
            }

            foreach ($this->client->taskComments($settings['webhook'], $task['id']) as $comment) {
                $day = $comment['posted_at']->copy()->timezone($timezone)->toDateString();

                if ($day < $fromDate || $day > $toDate) {
                    continue;
                }

                foreach (Bitrix24WorkParser::fromComment($comment['text']) as $title) {
                    $this->pushWork($works, $seen, $title, $day);
                }
            }
        }

        return $works;
    }

    /**
     * @param  list<array{title: string, completed_at: DateTimeImmutable}>  $works
     * @param  array<string, true>  $seen
     */
    private function pushWork(array &$works, array &$seen, string $title, string $day): void
    {
        $title = trim($title);

        if ($title === '') {
            return;
        }

        $key = $day."\n".$title;

        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $works[] = [
            'title' => $title,
            'completed_at' => new DateTimeImmutable($day),
        ];
    }

    private function failure(int $projectId, string $fromDate, string $toDate, string $message): IntegrationSyncResult
    {
        SafeLogger::warning('Integration sync: Bitrix24 works failed', [
            'project_id' => $projectId,
            'from' => $fromDate,
            'to' => $toDate,
            'message' => $message,
        ]);

        return IntegrationSyncResult::failure('Не удалось получить работы Битрикс24: '.$message, requeue: true);
    }
}
