<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ClientProjectVisibility;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClientProjectVisibilityTest extends TestCase
{
    public function test_manager_with_product_access_still_sees_only_own_clients(): void
    {
        $manager = $this->viewer(id: 7, manager: true, seesAll: false);
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
        $manager = $this->viewer(id: 7, manager: true, seesAll: true);
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

    public function test_specialist_sees_only_assigned_projects(): void
    {
        $specialist = $this->viewer(id: 9, manager: false, specialist: true, seesAll: false);
        $assigned = (object) ['id' => 10, 'client_id' => 1, 'specialist_id' => 9];
        $other = (object) ['id' => 11, 'client_id' => 1, 'specialist_id' => 4];

        [, $projects] = ClientProjectVisibility::limit(
            $specialist,
            new Collection([(object) ['id' => 1, 'manager_id' => 3]]),
            new Collection([$assigned, $other]),
        );

        $this->assertSame([10], $projects->pluck('id')->all());
    }

    private function viewer(int $id, bool $manager, bool $seesAll, bool $specialist = false): User
    {
        $user = new class extends User
        {
            public bool $managerFlag = false;

            public bool $specialistFlag = false;

            public bool $seesAllFlag = false;

            public function isManager(): bool
            {
                return $this->managerFlag;
            }

            public function isSpecialist(): bool
            {
                return $this->specialistFlag;
            }

            public function hasAnyPermission(...$permissions): bool
            {
                return $this->seesAllFlag;
            }
        };
        $user->id = $id;
        $user->managerFlag = $manager;
        $user->specialistFlag = $specialist;
        $user->seesAllFlag = $seesAll;

        return $user;
    }
}
