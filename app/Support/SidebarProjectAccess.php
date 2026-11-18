<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Src\Application\Clients\Access\ClientProjectAccessPolicy;

class SidebarProjectAccess
{
    public const SESSION_KEY = 'sidebar_selected_project_id';

    public static function userCanAccessProject(int $projectId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        $project = Project::query()->with('client')->find($projectId);

        if ($project === null) {
            return false;
        }

        return app(ClientProjectAccessPolicy::class)->userCanViewProjectAssignment(
            $user,
            $project->specialist_id !== null ? (int) $project->specialist_id : null,
            $project->client?->manager_id !== null ? (int) $project->client->manager_id : null,
        );
    }
}
