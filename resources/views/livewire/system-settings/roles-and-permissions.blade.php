<div
    wire:ignore.self
    x-data="{
        roles: $wire.entangle('roles'),
        editingRoleId: null,
    
        createRole() {
            this.roles.push({
                id: 'new-' + Date.now() + '-' + Math.random().toString().slice(2),
                name: 'Новая роль',
                permissions: @js($defaultPermissions),
                useInProjectFilter: false,
                isNew: true,
                childRoles: []
            });
        },
    
        deleteRole(roleId) {
            this.roles = this.roles.filter(r => r.id !== roleId);
        },
    
        startEdit(roleId) {
            this.editingRoleId = roleId;
        },
    
        stopEdit() {
            this.editingRoleId = null;
        },
    
        addChildRole(roleId) {
            this.roles.find(r => r.id === roleId)
                .childRoles.push({ id: '' });
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
        <div class="max-w-1/2">
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
                                        <x-icons.edit-2 x-on:click.stop="startEdit(role.id)" />
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

                                <span
                                    class="text-secondary-text mr-9 font-normal"
                                    x-on:click="deleteRole(role.id)"
                                >Удалить роль</span>
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
                                <x-form.toggle-switch x-model="role.useInProjectFilter" />
                            </div>
                            <div class="mb-5 flex justify-between">
                                <span>У роли есть подчиненные</span>
                                <x-form.toggle-switch x-model="role.hasChildRoles" />
                            </div>

                            <template x-if="role.hasChildRoles">
                                <div>
                                    <div class="flex flex-col gap-y-1">
                                        {{-- <span x-text="JSON.stringify(role.childRoles)"></span> --}}
                                        <template x-for="(childRole, index) in role.childRoles">
                                            <div class="flex">
                                                <x-form.select
                                                    x-bind:data-options="getChildOptions(role.id, childRole.id)"
                                                    x-model="childRole.id"
                                                />
                                                <x-button.button
                                                    label="Удалить"
                                                    variant="link"
                                                    x-on:click="deleteChildRole(role.id, childRole.id)"
                                                />
                                            </div>
                                        </template>
                                    </div>

                                    <x-button.button
                                        label="Добавить подчиненную роль"
                                        variant="link"
                                        x-on:click="addChildRole(role.id)"
                                    />
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
                                                <span x-text="permission.displayName"></span>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox x-model="permission.canRead" />
                                                </div>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox x-model="permission.canEdit" />
                                                </div>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox x-model="permission.haveFullAccess" />
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

    <div class="flex justify-between">
        <x-button.button
            label="Сохранить изменения"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
        />
        <x-button.button label="Отмена" />
    </div>
</div>
