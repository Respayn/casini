<?php

namespace Src\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Application\Clients\ClientReadRepositoryInterface;
use Src\Application\Clients\GetClientsWithProjects\ClientDto;
use Src\Application\Clients\GetClientsWithProjects\ClientProjectDto;
use Src\Domain\ValueObjects\ProjectType;

class ClientReadRepository implements ClientReadRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function getClientsWithProjects(): array
    {
        $rows = DB::table('clients')
            ->leftJoin('projects', 'clients.id', '=', 'projects.client_id')
            ->select([
                'clients.id as client_id',
                'clients.name as client_name',
                'clients.manager_id',
                'clients.inn',
                'clients.initial_balance',
                'projects.id as project_id',
                'projects.name as project_name',
                'projects.project_type'
            ])
            ->get();

        $grouped = $rows->groupBy('client_id');

        return $grouped->map(function ($clientRows) {
            $first = $clientRows->first();
            
            $projects = $clientRows->whereNotNull('project_id')->map(function ($row) {
                return new ClientProjectDto(
                    id: $row->project_id,
                    name: $row->project_name,
                    projectType: ProjectType::from($row->project_type)->label()
                );
            })->toArray();

            return new ClientDto(
                id: $first->client_id,
                name: $first->client_name,
                inn: $first->inn,
                initialBalance: (float) $first->initial_balance,
                managerId: (int) $first->manager_id,
                projects: $projects
            );
        })->values()->toArray();
    }
}