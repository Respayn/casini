<?php

namespace App\Services\Notifications;

use App\Models\Project;
use App\Models\User;
use App\Services\RoleHierarchyService;

class ProjectNotificationRecipientResolver
{
    public function __construct(
        private readonly RoleHierarchyService $roles,
    ) {}

    /**
     * @return list<int>
     */
    public function userIdsForProject(int $projectId): array
    {
        $project = Project::query()->with('client')->find($projectId);

        if ($project === null) {
            return [];
        }

        $assignedIds = array_values(array_filter([
            $project->specialist_id ? (int) $project->specialist_id : null,
            $project->client?->manager_id ? (int) $project->client->manager_id : null,
        ]));

        $users = User::query()
            ->where('is_active', true)
            ->where('enable_important_notifications', true)
            ->with(['roles.permissions', 'roles.childRoles.permissions', 'permissions'])
            ->get();

        $ids = [];

        foreach ($users as $user) {
            $isAssigned = in_array((int) $user->id, $assignedIds, true);
            $seesAll = $this->roles->userHasPermission($user, 'read clients and projects all')
                || $this->roles->userHasPermission($user, 'full clients and projects all');

            if ($isAssigned || $seesAll) {
                $ids[] = (int) $user->id;
            }
        }

        return array_values(array_unique($ids));
    }
}
