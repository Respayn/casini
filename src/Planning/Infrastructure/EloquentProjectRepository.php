<?php

namespace Src\Planning\Infrastructure;

use App\Models\Project as ProjectModel;
use Src\Planning\Application\Repositories\ProjectRepositoryInterface;
use Src\Planning\Domain\Client;
use Src\Planning\Domain\Project;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function find(int $id): ?Project
    {
        return $this->mapToDomainModel(
            ProjectModel::with('client')->find($id)
        );
    }

    private function mapToDomainModel(?ProjectModel $projectModel): ?Project
    {
        if ($projectModel === null) {
            return null;
        }

        $client = new Client(
            $projectModel->client->id,
            $projectModel->client->name
        );

        return new Project(
            $projectModel->id,
            $projectModel->name,
            $projectModel->created_at->toDateTimeImmutable(),
            $projectModel->project_type,
            $projectModel->kpi,
            $client
        );
    }
}
