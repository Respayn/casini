<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Src\Application\Clients\Access\ClientProjectAccessPolicy;

class ClientProjectVisibility
{
    /**
     * Те же границы, что у карточки клиенто-проекта (ClientProjectAccessPolicy).
     *
     * @param  Collection<int, mixed>  $clients
     * @param  Collection<int, mixed>  $projects
     * @return array{0: Collection<int, mixed>, 1: Collection<int, mixed>}
     */
    public static function limit(User $user, Collection $clients, Collection $projects): array
    {
        if (ClientsAndProjectsPermissions::userCanSeeAll($user)) {
            return [$clients, $projects];
        }

        if (! ClientsAndProjectsPermissions::userCanSeeSelf($user)) {
            return [collect(), collect()];
        }

        $policy = app(ClientProjectAccessPolicy::class);
        $clientsById = $clients->keyBy(fn ($client) => (int) $client->id);

        $projects = $projects->filter(function ($project) use ($user, $clientsById, $policy) {
            $client = $clientsById->get((int) $project->client_id);
            $managerId = $client?->manager_id !== null ? (int) $client->manager_id : null;
            $specialistId = $project->specialist_id !== null ? (int) $project->specialist_id : null;

            return $policy->userCanViewProjectAssignment($user, $specialistId, $managerId);
        })->values();

        $visibleClientIds = $projects->pluck('client_id')->map(fn ($id) => (int) $id)->unique();
        $clients = $clients->filter(
            fn ($client) => $visibleClientIds->contains((int) $client->id)
                || ((int) ($client->manager_id ?? 0) === (int) $user->id)
        )->values();

        return [$clients, $projects];
    }

    /**
     * Список всех клиенто-проектов (без выбранного id) сужается так же, как limit().
     */
    public static function restrictProjectQuery(Builder $query, ?User $user = null): void
    {
        $user ??= Auth::user();

        if ($user === null || ClientsAndProjectsPermissions::userCanSeeAll($user)) {
            return;
        }

        if (! ClientsAndProjectsPermissions::userCanSeeSelf($user)) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function (Builder $scoped) use ($user): void {
            $scoped
                ->where('specialist_id', $user->id)
                ->orWhereHas(
                    'client',
                    fn (Builder $client) => $client->where('manager_id', $user->id)
                );
        });
    }
}
