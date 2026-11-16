<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ClientProjectVisibility;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClientProjectVisibilityTest extends TestCase
{
    public function test_self_access_manager_sees_only_own_clients(): void
    {
        $manager = $this->viewer(id: 7, seesAll: false, seesSelf: true);
        $ownClient = (object) ['id' => 1, 'manager_id' => 7];
        $foreignClient = (object) ['id' => 2, 'manager_id' => 8];
        $ownProject = (object) ['id' => 10, 'client_id' => 1, 'specialist_id' => 9];
        $foreignProject = (object) ['id' => 11, 'client_id' => 2, 'specialist_id' => 9];

        [$clients, $projects] = ClientProjectVisibility::limit(
            $manager,
            new Collection([$ownClient, $foreignClient]),
            new Collection([$ownProject, $foreignProject]),
        );

        $this->assertSame([1], $clients->pluck('id')->all());
        $this->assertSame([10], $projects->pluck('id')->all());
    }

    public function test_all_access_keeps_every_project(): void
    {
        $manager = $this->viewer(id: 7, seesAll: true, seesSelf: false);
        $own = (object) ['id' => 10, 'client_id' => 1, 'specialist_id' => 9];
        $foreign = (object) ['id' => 11, 'client_id' => 2, 'specialist_id' => 9];

        [, $projects] = ClientProjectVisibility::limit(
            $manager,
            new Collection([
                (object) ['id' => 1, 'manager_id' => 7],
                (object) ['id' => 2, 'manager_id' => 8],
            ]),
            new Collection([$own, $foreign]),
        );

        $this->assertEqualsCanonicalizing([10, 11], $projects->pluck('id')->all());
    }

    public function test_self_access_specialist_sees_only_assigned_projects(): void
    {
        $specialist = $this->viewer(id: 9, seesAll: false, seesSelf: true);
        $assigned = (object) ['id' => 10, 'client_id' => 1, 'specialist_id' => 9];
        $other = (object) ['id' => 11, 'client_id' => 1, 'specialist_id' => 4];

        [, $projects] = ClientProjectVisibility::limit(
            $specialist,
            new Collection([(object) ['id' => 1, 'manager_id' => 3]]),
            new Collection([$assigned, $other]),
        );

        $this->assertSame([10], $projects->pluck('id')->all());
    }

    public function test_without_self_or_all_access_sees_nothing(): void
    {
        $viewer = $this->viewer(id: 5, seesAll: false, seesSelf: false);

        [$clients, $projects] = ClientProjectVisibility::limit(
            $viewer,
            new Collection([
                (object) ['id' => 1, 'manager_id' => 7],
                (object) ['id' => 2, 'manager_id' => 8],
            ]),
            new Collection([
                (object) ['id' => 10, 'client_id' => 1, 'specialist_id' => 9],
                (object) ['id' => 11, 'client_id' => 2, 'specialist_id' => 4],
            ]),
        );

        $this->assertSame([], $clients->pluck('id')->all());
        $this->assertSame([], $projects->pluck('id')->all());
    }

    private function viewer(int $id, bool $seesAll, bool $seesSelf): User
    {
        $user = new class extends User
        {
            public bool $seesAllFlag = false;

            public bool $seesSelfFlag = false;

            public function hasAnyPermission(...$permissions): bool
            {
                $names = collect($permissions)->flatten()->all();

                foreach ($names as $name) {
                    if (! is_string($name)) {
                        continue;
                    }

                    if ($this->seesAllFlag && str_ends_with($name, ' all')) {
                        return true;
                    }

                    if ($this->seesSelfFlag && str_ends_with($name, ' self')) {
                        return true;
                    }
                }

                return false;
            }
        };
        $user->id = $id;
        $user->seesAllFlag = $seesAll;
        $user->seesSelfFlag = $seesSelf;

        return $user;
    }
}
