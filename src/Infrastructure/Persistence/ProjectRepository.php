<?php

namespace Src\Infrastructure\Persistence;

use App\Models\Project as EloquentProject;
use Src\Domain\Projects\Project;
use Src\Domain\Projects\ProjectRepositoryInterface;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findAll(): array
    {
        $eloquentProjects = EloquentProject::all();

        return $eloquentProjects
            ->map(fn(EloquentProject $project) => $this->mapToEntity($project))
            ->all();
    }

    public function findById(int $id): Project
    {
        $eloquentProject = EloquentProject::find($id);
        return $this->mapToEntity($eloquentProject);
    }

    public function mapToEntity(EloquentProject $project): Project
    {
        return new Project(
            $project->id,
            $project->name,
            $project->client_id,
            $project->project_type,
            $project->specialist_id
        );
    }
}
