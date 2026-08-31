<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SidebarProjectAccess
{
    public const SESSION_KEY = 'sidebar_selected_project_id';

    public static function userCanAccessProject(int $projectId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        if (
            ! ClientsAndProjectsPermissions::userCanSeeAll($user)
            && ! ClientsAndProjectsPermissions::userCanSeeSelf($user)
        ) {
            return false;
        }

        $project = Project::query()->with('client')->find($projectId);

        if ($project === null) {
            return false;
        }

        if (ClientsAndProjectsPermissions::userCanSeeAll($user)) {
            return true;
        }

        if ($project->specialist_id === $user->id) {
            return true;
        }

        return $project->client?->manager_id === $user->id;
    }
}
