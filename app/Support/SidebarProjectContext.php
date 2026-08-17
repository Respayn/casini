<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SidebarProjectContext
{
    public const SESSION_KEY = 'sidebar_selected_project_id';

    public function get(): ?int
    {
        $projectId = Session::get(self::SESSION_KEY);

        if ($projectId === null) {
            return null;
        }

        $projectId = (int) $projectId;

        if (! $this->userCanAccessProject($projectId)) {
            $this->clear();

            return null;
        }

        return $projectId;
    }

    public function set(int $projectId): bool
    {
        if (! $this->userCanAccessProject($projectId)) {
            return false;
        }

        Session::put(self::SESSION_KEY, $projectId);

        return true;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function userCanAccessProject(int $projectId, ?User $user = null): bool
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
