<?php

namespace App\Services;

use App\Data\RoleData;
use App\Data\SystemSettings\PermissionEditData;
use App\Data\SystemSettings\RoleEditData;
use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;
use App\OperationResult;
use App\Repositories\RoleRepository;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getRoleOptions(): array
    {
        return Role::query()
            ->orderBy('id')
            ->get()
            ->map(function ($role) {
                return [
                    'value' => $role->id,
                    'label' => $role->display_name
                ];
            })->toArray();
    }

    /**
     * Возвращает роли с собранными по группам правами для страницы настроек.
     *
     * Для каждой роли формирует DTO RoleEditData, где права сгруппированы по имени группы (PermissionGroup)
     * и представлены как PermissionEditData с флагами уровней доступа: read, edit и full.
     *
     * Источники данных:
     * - RoleRepository::getRolesWithPermissions() — роли с загруженными правами
     * - RoleRepository::getPermissions() — полный список прав для определения групп
     *
     * @return array
     * Коллекция DTO для отображения/редактирования прав на UI.
     */
    public function getRolesAndPermissionsForSettingsPage()
    {
        $roles = $this->roleRepository->getRolesWithPermissions();
        $permissions = $this->roleRepository->getPermissions();
        $permissionGroups = $permissions
            ->groupBy('group')
            ->filter(function ($permissions, $groupName) {
                return ! PermissionGroup::from($groupName)->isHiddenOnSettingsPage();
            });

        return $roles->map(function (RoleData $role) use ($permissionGroups) {
            $permissions = $permissionGroups->map(function ($permissions, $groupName) use ($role) {
                return new PermissionEditData(
                    $groupName,
                    PermissionGroup::from($groupName)->label(),
                    $role->permissions->contains(fn ($perm) => $perm->name === ('read ' . $groupName)),
                    $role->permissions->contains(fn ($perm) => $perm->name === ('edit ' . $groupName)),
                    $role->permissions->contains(fn ($perm) => $perm->name === ('full ' . $groupName)),
                    PermissionGroup::from($groupName)->isSecondary()
                );
            })->values()->all();

            $permissions = $this->sortPermissionsForSettingsPage($permissions);
            $permissions = $this->normalizeRolePermissions($role->name, $permissions);

            return new RoleEditData(
                (string) $role->id,
                $role->displayName ?? $role->name,
                $permissions,
                $role->useInProjectFilter,
                $role->useInManagersList,
                $role->useInSpecialistList,
                $role->childRoles->isNotEmpty(),
                $role->childRoles,
                $role->name,
                $role->hasAssignedUsers,
            );
        })->toArray();
    }

    /**
     * Возвращает список групп прав с дефолтными (ложными) флагами доступа для страницы настроек.
     *
     * Используется при создании новой роли либо при первичной инициализации формы,
     * когда у роли ещё нет назначенных прав — все флаги (read, edit, full) выставляются в false.
     * Группы определяются на основании полного списка прав из RoleRepository::getPermissions(),
     * после чего преобразуются в массив DTO PermissionEditData.
     *
     * @return PermissionEditData[]
     * Массив DTO с группами прав и флагами по умолчанию.
     */
    public function getPermissionsWithDefaultValuesForSettingsPage()
    {
        $permissions = $this->roleRepository->getPermissions();
        $permissionGroups = $permissions
            ->groupBy('group')
            ->filter(function ($permissions, $groupName) {
                return ! PermissionGroup::from($groupName)->isHiddenOnSettingsPage();
            })
            ->toArray();

        $permissions = array_map(function ($groupName) {
            return new PermissionEditData(
                $groupName,
                PermissionGroup::from($groupName)->label(),
                false,
                false,
                false,
                PermissionGroup::from($groupName)->isSecondary()
            );
        }, array_keys($permissionGroups));

        return $this->sortPermissionsForSettingsPage($permissions);
    }

    /**
     * @param  array<int, array|PermissionEditData>  $permissions
     * @return array<int, array|PermissionEditData>
     */
    private function sortPermissionsForSettingsPage(array $permissions): array
    {
        $order = array_flip(PermissionGroup::settingsPageGroupOrder());

        usort($permissions, function ($left, $right) use ($order) {
            $leftName = is_array($left) ? ($left['name'] ?? '') : $left->name;
            $rightName = is_array($right) ? ($right['name'] ?? '') : $right->name;

            $leftIndex = $order[$leftName] ?? PHP_INT_MAX;
            $rightIndex = $order[$rightName] ?? PHP_INT_MAX;

            return $leftIndex <=> $rightIndex;
        });

        return array_values($permissions);
    }

    public function saveChanges(array $roles): OperationResult
    {
        $result = OperationResult::success();
        $roles = collect($roles);

        DB::beginTransaction();

        $incomingExistingIds = $roles
            ->map(fn ($r) => is_numeric($r['id']) ? (int) $r['id'] : null)
            ->filter()
            ->values();

        $existingRoles = $this->roleRepository->getRoles();
        $adminRoleIds = $existingRoles
            ->filter(fn (RoleData $role) => $role->name === RoleEnum::ADMIN->value)
            ->pluck('id');

        $idsToDelete = $existingRoles
            ->pluck('id')
            ->diff($incomingExistingIds)
            ->diff($adminRoleIds);

        if ($idsToDelete->isNotEmpty()) {
            foreach ($idsToDelete as $id) {
                try {
                    $this->roleRepository->deleteRole($id);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return OperationResult::failure($e->getMessage());
                }
            }
        }

        foreach ($roles as $roleDto) {
            $systemName = $roleDto['systemName']
                ?? (is_numeric($roleDto['id'])
                    ? ($existingRoles->firstWhere('id', (int) $roleDto['id'])?->name ?? '')
                    : '');

            $roleDto['permissions'] = $this->normalizeRolePermissions(
                $systemName,
                array_values($roleDto['permissions'] ?? [])
            );

            $isNew = ! is_numeric($roleDto['id']);

            if ($isNew) {
                $result = $this->roleRepository->createRole($roleDto);
                if ($result->isFailure()) {
                    DB::rollBack();

                    return $result;
                }
            } else {
                $result = $this->roleRepository->updateRole($roleDto);
                if ($result->isFailure()) {
                    DB::rollBack();

                    return $result;
                }
            }
        }

        DB::commit();

        return $result;
    }

    /**
     * @param  array<int, array|PermissionEditData>  $permissions
     * @return array<int, array>
     */
    private function normalizeRolePermissions(string $systemName, array $permissions): array
    {
        $isAdmin = $systemName === RoleEnum::ADMIN->value;

        return array_map(function ($permission) use ($isAdmin) {
            $permission = is_array($permission) ? $permission : $permission->toArray();
            $groupName = $permission['name'] ?? '';

            if ($isAdmin) {
                $permission['canRead'] = true;
                $permission['canEdit'] = true;
                $permission['haveFullAccess'] = true;

                return $permission;
            }

            try {
                $group = PermissionGroup::from($groupName);
            } catch (\ValueError) {
                return $permission;
            }

            if ($group->isFullAccessLockedForNonAdmin()) {
                $permission['haveFullAccess'] = false;
            }

            if ($group->isEditAccessLockedForNonAdmin()) {
                $permission['canEdit'] = false;
            }

            if ($permission['haveFullAccess']) {
                $permission['canEdit'] = true;
                $permission['canRead'] = true;
            } elseif ($permission['canEdit']) {
                $permission['canRead'] = true;
            }

            return $permission;
        }, $permissions);
    }
}
