<?php

namespace App\Services;

use App\Data\RoleData;
use App\Data\SystemSettings\PermissionEditData;
use App\Data\SystemSettings\RoleEditData;
use App\Enums\PermissionGroup;
use App\Models\User;
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
        $permissionGroups = $permissions->groupBy('group');

        return $roles->map(function (RoleData $role) use ($permissionGroups) {
            return new RoleEditData(
                $role->id,
                $role->displayName,
                $permissionGroups->map(function ($permissions, $groupName) use ($role) {
                    return new PermissionEditData(
                        $groupName,
                        PermissionGroup::from($groupName)->label(),
                        $role->permissions->containsOneItem(fn($perm) => $perm->name === ('read ' . $groupName)),
                        $role->permissions->containsOneItem(fn($perm) => $perm->name === ('edit ' . $groupName)),
                        $role->permissions->containsOneItem(fn($perm) => $perm->name === ('full ' . $groupName))
                    );
                })->toArray(),
                $role->useInProjectFilter,
                $role->useInManagersList,
                $role->useInSpecialistList,
                $role->childRoles->isNotEmpty(),
                $role->childRoles

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
        $permissionGroups = $permissions->groupBy('group')->toArray();

        return array_map(function ($groupName, $permissions) {
            return new PermissionEditData(
                $groupName,
                PermissionGroup::from($groupName)->label(),
                false,
                false,
                false
            );
        }, array_keys($permissionGroups), $permissionGroups);
    }

    public function saveChanges(array $roles): OperationResult
    {
        $result = OperationResult::success();
        $roles = collect($roles);

        DB::beginTransaction();

        $incomingExistingIds = $roles
            ->map(fn($r) => is_numeric($r['id']) ? (int) $r['id'] : null)
            ->filter()
            ->values();

        $existingIds = $this->roleRepository->getRoles()->pluck('id');
        $idsToDelete = $existingIds->diff($incomingExistingIds);

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
            $isNew = !is_numeric($roleDto['id']);

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
}
