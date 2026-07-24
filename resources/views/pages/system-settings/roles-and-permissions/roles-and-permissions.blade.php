@php
    use App\Enums\PermissionGroup;
    use App\Enums\Role as RoleEnum;

    $permissionRules = [
        'adminSystemName' => RoleEnum::ADMIN->value,
        'fullAccessLocked' => PermissionGroup::fullAccessLockedGroupNames(),
        'editAccessLocked' => PermissionGroup::editAccessLockedGroupNames(),
    ];
@endphp

<div
    wire:ignore.self
    x-data="{
        initialRoles: null,
        roles: $wire.entangle('roles'),
        editingRoleId: null,
        hasPendingChanges: false,
        permissionRules: @js($permissionRules),

        init() {
            this.initialRoles = JSON.parse(JSON.stringify(this.roles));
        },

        isAdminRole(role) {
            return role.systemName === this.permissionRules.adminSystemName;
        },

        isAllPermissionsDisabled(role) {
            return this.isAdminRole(role);
        },

        isFullAccessDisabled(role, permission) {
            return this.isAdminRole(role)
                || this.permissionRules.fullAccessLocked.includes(permission.name);
        },

        isEditAccessDisabled(role, permission) {
            return this.isAdminRole(role)
                || this.permissionRules.editAccessLocked.includes(permission.name)
                || this.isEditInherited(role, permission);
        },

        isReadAccessDisabled(role, permission) {
            return this.isAdminRole(role)
                || this.isReadInherited(role, permission);
        },

        isReadInherited(role, permission) {
            return !this.isAdminRole(role)
                && (permission.haveFullAccess || permission.canEdit);
        },

        isEditInherited(role, permission) {
            return !this.isAdminRole(role) && permission.haveFullAccess;
        },

        syncInheritedPermissions(permission) {
            if (permission.haveFullAccess) {
                permission.canEdit = true;
                permission.canRead = true;
            } else if (permission.canEdit) {
                permission.canRead = true;
            }
        },

        markPendingIfEnabled(event) {
            if (event.target.disabled) {
                event.preventDefault();
                return;
            }

            this.hasPendingChanges = true;
        },

        createRole() {
            this.roles.push({
                id: 'new-' + Date.now() + '-' + Math.random().toString().slice(2),
                name: 'Новая роль',
                systemName: '',
                hasAssignedUsers: false,
                permissions: @js($this->defaultPermissions),
                useInProjectFilter: false,
                useInManagersList: false,
                useInSpecialistList: false,
                isNew: true,
                childRoles: []
            });
            this.hasPendingChanges = true;
        },

        deleteRole(roleId) {
            const role = this.roles.find(r => r.id === roleId);
            if (role && this.isAdminRole(role)) {
                return;
            }

            this.roles = this.roles.filter(r => r.id !== roleId);
            this.hasPendingChanges = true;
        },

        startEdit(roleId) {
            this.editingRoleId = roleId;
        },

        stopEdit() {
            this.editingRoleId = null;
            this.hasPendingChanges = true;
        },

        addChildRole(roleId) {
            this.roles.find(r => r.id === roleId)
                .childRoles.push({ id: '' });
            this.hasPendingChanges = true;
        },

        getChildOptions(roleId, currentChildId) {
            const parentRole = this.roles.find(r => r.id === roleId);
            if (!parentRole) return '[]';

            const selectedChildIds = parentRole.childRoles
                .filter(child => child.id && child.id !== currentChildId)
                .map(child => child.id);

            const availableRoles = this.roles.filter(role =>
                role.id !== roleId &&
                !selectedChildIds.includes(role.id)
            );

            return JSON.stringify(
                availableRoles.map(role => ({
                    label: role.name,
                    value: role.id
                }))
            );
        },

        deleteChildRole(roleId, childRoleId) {
            const parentRole = this.roles.find(r => r.id === roleId);
            if (parentRole) {
                parentRole.childRoles = parentRole.childRoles.filter(child => child.id !== childRoleId);
            }
            this.hasPendingChanges = true;
        },

        resetChanges() {
            this.roles = JSON.parse(JSON.stringify(this.initialRoles));
            this.editingRoleId = null;
            this.hasPendingChanges = false;
        }
    }"
>
    {{-- Заголовок --}}
    <h1 class="mb-7">Продукты и права</h1>

    {{-- Блок с ролями --}}
    <x-panel.scroll-panel
        class="mb-3"
        style="max-height: calc(100vh - 300px);"
    >
        <div class="xl:max-w-1/2">
            <x-panel.accordion class="mb-3">
                <template
                    x-for="role in roles"
                    :key="'role.' + role.id"
                >
                    <x-panel.accordion-panel>
                        <x-panel.accordion-header>
                            <div class="flex items-center justify-between">
                                <template x-if="editingRoleId !== role.id">
                                    <div class="flex gap-x-3 text-[#599CFF]">
                                        @canany(['edit system settings', 'full system settings'])
                                            <x-icons.edit-2
                                                class="hover:text-[#4070E0]"
                                                x-on:click.stop="startEdit(role.id)"
                                            />
                                        @endcan
                                        <span
                                            class="underline"
                                            x-text="role.name"
                                        ></span>
                                    </div>
                                </template>
                                <template x-if="editingRoleId === role.id">
                                    <div class="flex gap-x-3">
                                        <x-form.input-text
                                            type="text"
                                            x-model="role.name"
                                            x-on:keydown.enter.prevent="stopEdit()"
                                            x-on:keydown.escape.prevent="stopEdit()"
                                            x-on:blur="stopEdit()"
                                            x-init="$el.focus()"
                                        />
                                    </div>
                                </template>

                                @canany(['edit system settings', 'full system settings'])
                                    <template x-if="isAdminRole(role)">
                                        <div
                                            class="relative mr-9 inline-block"
                                            x-data="{ open: false }"
                                        >
                                            <span
                                                x-ref="deleteTrigger"
                                                @mouseenter="open = true"
                                                @mouseleave="open = false"
                                            >
                                                <x-button.button
                                                    class="text-secondary-text font-normal"
                                                    variant="link"
                                                    label="Удалить роль"
                                                    disabled
                                                />
                                            </span>
                                            <template x-teleport="body">
                                                <div
                                                    class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                                    style="z-index: 1000"
                                                    x-show="open"
                                                    x-cloak
                                                    x-anchor.bottom="$refs.deleteTrigger"
                                                >
                                                    Нельзя удалить роль администратор
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!isAdminRole(role) && role.hasAssignedUsers">
                                        <div
                                            class="relative mr-9 inline-block"
                                            x-data="{ open: false }"
                                        >
                                            <span
                                                x-ref="deleteTrigger"
                                                @mouseenter="open = true"
                                                @mouseleave="open = false"
                                            >
                                                <x-button.button
                                                    class="text-secondary-text font-normal"
                                                    variant="link"
                                                    label="Удалить роль"
                                                    disabled
                                                />
                                            </span>
                                            <template x-teleport="body">
                                                <div
                                                    class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                                    style="z-index: 1000"
                                                    x-show="open"
                                                    x-cloak
                                                    x-anchor.bottom="$refs.deleteTrigger"
                                                >
                                                    Нельзя удалить роль, пока к ней привязаны пользователи
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!isAdminRole(role) && !role.hasAssignedUsers">
                                        <x-button.button
                                            class="text-secondary-text mr-9 font-normal"
                                            variant="link"
                                            x-on:click="deleteRole(role.id)"
                                            label="Удалить роль"
                                        />
                                    </template>
                                @endcan
                            </div>
                        </x-panel.accordion-header>
                        <x-panel.accordion-content>
                            <div class="mb-2.5 flex justify-between">
                                <span>
                                    Собрать портфель клиенто-проектов
                                    <x-overlay.tooltip>
                                        Если активировать, то по этой роли в виджете станет доступен список клиентов и
                                        клиенто-проектов
                                    </x-overlay.tooltip>
                                </span>
                                <x-form.toggle-switch
                                    x-model="role.useInProjectFilter"
                                    x-on:click="hasPendingChanges = true;"
                                />
                            </div>
                            <div class="mb-2 flex justify-between">
                                <span>У роли есть подчиненные</span>
                                <x-form.toggle-switch
                                    x-model="role.hasChildRoles"
                                    x-on:click="hasPendingChanges = true;"
                                />
                            </div>

                            <template x-if="role.hasChildRoles">
                                <div class="mb-4">
                                    <div class="flex flex-col gap-y-1">
                                        <template x-for="(childRole, index) in role.childRoles">
                                            <div class="flex">
                                                <x-form.select
                                                    x-bind:data-options="getChildOptions(role.id, childRole.id)"
                                                    x-model="childRole.id"
                                                />
                                                <x-button.button
                                                    class="text-secondary-text"
                                                    label="Удалить"
                                                    variant="link"
                                                    x-on:click="deleteChildRole(role.id, childRole.id)"
                                                />
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-1 flex justify-start">
                                        <x-button.button
                                            class="self-start"
                                            label="Добавить подчиненную роль"
                                            variant="outlined"
                                            icon="icons.plus"
                                            x-on:click="addChildRole(role.id)"
                                        />
                                    </div>
                                </div>
                            </template>

                            <x-data.table class="w-full">
                                <x-data.table-columns>
                                    <x-data.table-column>Продукт</x-data.table-column>
                                    <x-data.table-column>Чтение</x-data.table-column>
                                    <x-data.table-column>Изменение</x-data.table-column>
                                    <x-data.table-column>
                                        Полный доступ
                                    </x-data.table-column>
                                </x-data.table-columns>
                                <x-data.table-rows>
                                    <template
                                        x-for="permission in role.permissions"
                                        :key="'role.' + role.id + '.permission.' + permission.name"
                                    >
                                        <x-data.table-row>
                                            <x-data.table-cell>
                                                <div class="flex gap-x-2.5 items-center">
                                                    <template x-if="permission.isSecondary">
                                                        <x-icons.accordion-arrow class="rotate-270 size-2.5 shrink-0 text-secondary-text" />
                                                    </template>
                                                    <span x-text="permission.displayName"></span>
                                                </div>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <template x-if="isReadInherited(role, permission)">
                                                        <div
                                                            class="relative inline-block"
                                                            x-data="{ open: false }"
                                                        >
                                                            <span
                                                                x-ref="readInheritedTrigger"
                                                                @mouseenter="open = true"
                                                                @mouseleave="open = false"
                                                            >
                                                                <x-form.checkbox
                                                                    x-model="permission.canRead"
                                                                    disabled
                                                                />
                                                            </span>
                                                            <template x-teleport="body">
                                                                <div
                                                                    class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                                                    style="z-index: 1000"
                                                                    x-show="open"
                                                                    x-cloak
                                                                    x-anchor.bottom="$refs.readInheritedTrigger"
                                                                >
                                                                    {{ __('permissions.inherited') }}
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="!isReadInherited(role, permission)">
                                                        <x-form.checkbox
                                                            x-model="permission.canRead"
                                                            x-bind:disabled="isReadAccessDisabled(role, permission)"
                                                            x-on:click="markPendingIfEnabled($event)"
                                                        />
                                                    </template>
                                                </div>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <template x-if="isEditInherited(role, permission)">
                                                        <div
                                                            class="relative inline-block"
                                                            x-data="{ open: false }"
                                                        >
                                                            <span
                                                                x-ref="editInheritedTrigger"
                                                                @mouseenter="open = true"
                                                                @mouseleave="open = false"
                                                            >
                                                                <x-form.checkbox
                                                                    x-model="permission.canEdit"
                                                                    disabled
                                                                />
                                                            </span>
                                                            <template x-teleport="body">
                                                                <div
                                                                    class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                                                    style="z-index: 1000"
                                                                    x-show="open"
                                                                    x-cloak
                                                                    x-anchor.bottom="$refs.editInheritedTrigger"
                                                                >
                                                                    {{ __('permissions.inherited') }}
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="!isEditInherited(role, permission)">
                                                        <x-form.checkbox
                                                            x-model="permission.canEdit"
                                                            x-bind:disabled="isEditAccessDisabled(role, permission)"
                                                            x-on:change="syncInheritedPermissions(permission)"
                                                            x-on:click="markPendingIfEnabled($event)"
                                                        />
                                                    </template>
                                                </div>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox
                                                        x-model="permission.haveFullAccess"
                                                        x-bind:disabled="isFullAccessDisabled(role, permission)"
                                                        x-on:change="syncInheritedPermissions(permission)"
                                                        x-on:click="markPendingIfEnabled($event)"
                                                    />
                                                </div>
                                            </x-data.table-cell>
                                        </x-data.table-row>
                                    </template>
                                </x-data.table-rows>
                            </x-data.table>
                        </x-panel.accordion-content>
                    </x-panel.accordion-panel>
                </template>
            </x-panel.accordion>

            <div class="flex justify-end">
                <x-button.button
                    x-on:click="createRole()"
                    variant="primary"
                    label="Добавить роль"
                    icon="icons.plus"
                />
            </div>
        </div>
    </x-panel.scroll-panel>

    <template x-if="hasPendingChanges">
        <div class="flex justify-between">
            <x-button.button
                label="Сохранить изменения"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
            />
            <x-button.button
                label="Отмена"
                x-on:click="resetChanges()"
            />
        </div>
    </template>
</div>
