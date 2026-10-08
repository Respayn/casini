<?php

namespace App\Services;

use App\Data\Sidebar\EmployeeData;
use App\Data\Sidebar\SidebarClientData;
use App\Data\Sidebar\SidebarProjectData;
use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Support\ClientsAndProjectsPermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SidebarService
{
    public function __construct(
        private RoleRepository $roleRepository,
    ) {}

    /**
     * @return list<array{label: string, value: string}>
     */
    public function getRoleOptions(): array
    {
        return $this->roleRepository->getRolesForFilter()
            ->map(function ($role) {
                $label = $role->useInManagersList
                    ? 'По менеджерам'
                    : 'По роли '.$role->displayName;

                return [
                    'label' => $label,
                    'value' => (string) $role->id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, EmployeeData>
     */
    public function getEmployees(?string $sortBy, ?string $searchQuery): array
    {
        if ($sortBy === null || $sortBy === '') {
            return [];
        }

        $viewer = Auth::user();
        if ($viewer === null) {
            return [];
        }

        if (
            ! ClientsAndProjectsPermissions::userCanSeeAll($viewer)
            && ! ClientsAndProjectsPermissions::userCanSeeSelf($viewer)
        ) {
            return [];
        }

        $role = Role::query()->find((int) $sortBy);
        if ($role === null || ! $role->use_in_project_filter) {
            return [];
        }

        $usersQuery = User::role($role->name)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (! ClientsAndProjectsPermissions::userCanSeeAll($viewer)) {
            $usersQuery->where('id', $viewer->id);
        }

        $users = $usersQuery->get();
        $isManagerRole = (bool) $role->use_in_managers_list;
        $search = filled($searchQuery) ? Str::lower(trim($searchQuery)) : null;

        $employees = [];

        foreach ($users as $user) {
            $clients = $isManagerRole
                ? $this->portfolioForManager($user)
                : $this->portfolioForSpecialist($user);

            if ($search !== null) {
                $clients = $this->filterClientsBySearch($clients, $user, $search);
            }

            if ($clients === []) {
                continue;
            }

            $employees[$user->id] = new EmployeeData(
                id: $user->id,
                name: $this->formatUserName($user),
                clients: $clients,
            );
        }

        if (count($employees) === 1) {
            $this->expandEntireTree($employees);
        }

        return $employees;
    }

    /**
     * Если в сайдбаре один сотрудник — сразу показываем всех клиентов и проекты.
     *
     * @param  array<int, EmployeeData>  $employees
     */
    private function expandEntireTree(array $employees): void
    {
        foreach ($employees as $employee) {
            $employee->open = true;

            foreach ($employee->clients as $client) {
                $client->open = true;
            }
        }
    }

    /**
     * @return array<int, SidebarClientData>
     */
    private function portfolioForManager(User $user): array
    {
        $clients = Client::query()
            ->where('manager_id', $user->id)
            ->with(['projects' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $result = [];

        foreach ($clients as $client) {
            $projects = [];
            foreach ($client->projects as $project) {
                $projects[$project->id] = new SidebarProjectData(
                    id: $project->id,
                    name: $project->name,
                );
            }

            if ($projects === []) {
                continue;
            }

            $result[$client->id] = new SidebarClientData(
                id: $client->id,
                name: $client->name,
                projects: $projects,
            );
        }

        return $result;
    }

    /**
     * @return array<int, SidebarClientData>
     */
    private function portfolioForSpecialist(User $user): array
    {
        $projects = Project::query()
            ->where('specialist_id', $user->id)
            ->where('is_active', true)
            ->with('client')
            ->orderBy('name')
            ->get();

        /** @var Collection<int, Collection<int, Project>> $grouped */
        $grouped = $projects->groupBy('client_id');
        $result = [];

        foreach ($grouped as $clientId => $clientProjects) {
            $client = $clientProjects->first()?->client;
            if ($client === null) {
                continue;
            }

            $projectItems = [];
            foreach ($clientProjects as $project) {
                $projectItems[$project->id] = new SidebarProjectData(
                    id: $project->id,
                    name: $project->name,
                );
            }

            $result[(int) $clientId] = new SidebarClientData(
                id: (int) $clientId,
                name: $client->name,
                projects: $projectItems,
            );
        }

        uasort(
            $result,
            fn (SidebarClientData $a, SidebarClientData $b) => strcmp($a->name, $b->name)
        );

        return $result;
    }

    /**
     * @param  array<int, SidebarClientData>  $clients
     * @return array<int, SidebarClientData>
     */
    private function filterClientsBySearch(array $clients, User $user, string $search): array
    {
        $userName = Str::lower($this->formatUserName($user));
        if (Str::contains($userName, $search)) {
            return $clients;
        }

        $filtered = [];

        foreach ($clients as $clientId => $client) {
            $clientName = Str::lower($client->name);
            if (Str::contains($clientName, $search)) {
                $filtered[$clientId] = $client;

                continue;
            }

            $matchedProjects = [];
            foreach ($client->projects as $projectId => $project) {
                if (Str::contains(Str::lower($project->name), $search)) {
                    $matchedProjects[$projectId] = $project;
                }
            }

            if ($matchedProjects !== []) {
                $filtered[$clientId] = new SidebarClientData(
                    id: $client->id,
                    name: $client->name,
                    projects: $matchedProjects,
                    open: $client->open,
                );
            }
        }

        return $filtered;
    }

    private function formatUserName(User $user): string
    {
        return trim(implode(' ', array_filter([
            $user->first_name,
            $user->last_name,
        ]))) ?: ($user->login ?? '—');
    }
}
