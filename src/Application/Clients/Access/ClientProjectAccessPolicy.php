<?php

namespace Src\Application\Clients\Access;

use App\Data\ProjectData;
use App\Models\User;
use App\Support\ClientsAndProjectsPermissions;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ClientProjectAccessPolicy
{
    public function ensureUserCanViewProject(User $user, ProjectData $project, ?int $clientManagerId): void
    {
        if ($this->userCanViewProject($user, $project, $clientManagerId)) {
            return;
        }

        throw UnauthorizedException::forPermissions(ClientsAndProjectsPermissions::readPermissionNames());
    }

    public function userCanViewProject(User $user, ProjectData $project, ?int $clientManagerId): bool
    {
        if (ClientsAndProjectsPermissions::userCanSeeAll($user)) {
            return true;
        }

        if (! ClientsAndProjectsPermissions::userCanSeeSelf($user)) {
            return false;
        }

        if ($clientManagerId !== null && $clientManagerId === $user->id) {
            return true;
        }

        return $project->specialist_id !== null && $project->specialist_id === $user->id;
    }
}
