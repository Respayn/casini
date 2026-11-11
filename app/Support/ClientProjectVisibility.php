<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ClientProjectVisibility
{
    private static function seesAllProjects(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission([
            'read clients and projects all',
            'full clients and projects all',
        ]);
    }

    /**
     * Те же границы, что у Каналов: менеджер видит своих клиентов,
     * специалист видит проекты, где он назначен.
     *
     * @param  Collection<int, mixed>  $clients
     * @param  Collection<int, mixed>  $projects
     * @return array{0: Collection<int, mixed>, 1: Collection<int, mixed>}
     */
    public static function limit(User $user, Collection $clients, Collection $projects): array
    {
        if ($user->isManager() && ! self::seesAllProjects($user)) {
            $clients = $clients->filter(
                fn ($client) => $client->manager_id === $user->id
            )->values();
        }

        $clientIds = $clients->pluck('id');
        $projects = $projects->filter(
            fn ($project) => $clientIds->contains($project->client_id)
        )->values();

        if ($user->isSpecialist() && ! self::seesAllProjects($user)) {
            $projects = $projects->filter(
                fn ($project) => $project->specialist_id === $user->id
            )->values();
        }

        return [$clients, $projects];
    }

    /**
     * Список всех клиенто-проектов (без выбранного id) сужается так же, как limit().
     */
    public static function restrictProjectQuery(Builder $query, ?User $user = null): void
    {
        $user ??= Auth::user();

        if ($user === null || self::seesAllProjects($user)) {
            return;
        }

        if ($user->isManager()) {
            $query->whereHas(
                'client',
                fn (Builder $client) => $client->where('manager_id', $user->id)
            );
        }

        if ($user->isSpecialist()) {
            $query->where('specialist_id', $user->id);
        }
    }
}
