<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\CompletedWork as EloquentCompletedWork;
use Src\Domain\CompletedWorks\CompletedWork;
use Src\Domain\CompletedWorks\CompletedWorkRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class EloquentCompletedWorkRepository implements CompletedWorkRepositoryInterface
{
    /**
     * @return CompletedWork[]
     */
    public function findByProjectId(int $projectId, DateTimeRange $period): array
    {
        $works = EloquentCompletedWork::query()
            ->where('project_id', $projectId)
            ->whereBetween('completed_at', [$period->start, $period->end])
            ->orderBy('completed_at')
            ->get();

        return $works->map(fn(EloquentCompletedWork $work) => $this->mapToEntity($work))->all();
    }

    public function saveMany(int $projectId, array $works): void
    {
        foreach ($works as $work) {
            EloquentCompletedWork::create([
                'project_id' => $projectId,
                'title' => $work['title'],
                'completed_at' => $work['completed_at']->format('Y-m-d'),
            ]);
        }
    }

    private function mapToEntity(EloquentCompletedWork $work): CompletedWork
    {
        return new CompletedWork(
            $work->id,
            $work->project_id,
            $work->title,
            \DateTimeImmutable::createFromMutable($work->completed_at)
        );
    }
}
