<?php

use App\Enums\PermissionGroup;
use App\Services\ClientService;
use App\Services\UserService;
use App\Support\ClientsAndProjectsPermissions;
use App\Support\SystemSettingsSectionPermissions;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Src\Application\Clients\Create\CreateClientCommand;
use Src\Application\Clients\Create\CreateClientCommandHandler;
use Src\Application\Clients\Update\UpdateClientCommand;
use Src\Application\Clients\Update\UpdateClientCommandHandler;

new class extends Component
{
    public ?int $id = null;

    public string $name;

    public string $inn;

    public int $managerId;

    public float $initialBalance;

    public ?string $deleteError = null;

    private UserService $userService;

    public function boot(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function mount(): void
    {
        if (request()->boolean('createClient')) {
            $this->onClientCreate();
        }
    }

    #[On('client-create')]
    public function onClientCreate()
    {
        ClientsAndProjectsPermissions::ensureUserCanEdit(Auth::user());

        $this->reset();
        $this->deleteError = null;
        $this->dispatch('modal-show', name: 'client-modal');
    }

    #[On('client-edit')]
    public function onClientEdit($id, $name, $inn, $initialBalance, $managerId)
    {
        ClientsAndProjectsPermissions::ensureUserCanEdit(Auth::user());

        $this->id = $id;
        $this->name = $name;
        $this->inn = $inn;
        $this->initialBalance = $initialBalance;
        $this->managerId = $managerId;
        $this->deleteError = null;
        $this->dispatch('modal-show', name: 'client-modal');
    }

    #[Computed]
    public function canEdit(): bool
    {
        return ClientsAndProjectsPermissions::userCanEdit(Auth::user());
    }

    #[Computed]
    public function canDeleteClient(): bool
    {
        return $this->id !== null
            && SystemSettingsSectionPermissions::userCanFull(PermissionGroup::CLIENTS_AND_PROJECTS);
    }

    #[Computed]
    public function modalTitle()
    {
        return $this->id === null ? 'Создание клиента' : 'Редактирование клиента';
    }

    #[Computed]
    public function confirmButtonLabel()
    {
        return $this->id === null ? 'Создать' : 'Сохранить';
    }

    #[Computed]
    public function managerOptions()
    {
        $currentAgencyId = session('current_agency_id') ?? (Auth::user()->agency_id ?? null);

        return $this->userService
            ->getManagers($currentAgencyId)
            ->map(fn ($manager) => [
                'label' => $this->formatManagerName($manager),
                'value' => $manager->id,
            ])
            ->values()
            ->all();
    }

    private function formatManagerName($manager): string
    {
        $fullName = trim("{$manager->first_name} {$manager->last_name}");

        return $fullName !== '' ? $fullName : $manager->login;
    }

    public function saveClient(CreateClientCommandHandler $createCommand, UpdateClientCommandHandler $updateCommand)
    {
        ClientsAndProjectsPermissions::ensureUserCanEdit(Auth::user());

        $this->validate([
            'name' => 'required|string|max:255',
            'inn' => [
                'required',
                'regex:/^\d{10,12}$/',
                'unique:clients,inn,'.($this->id ?: 'null'),
            ],
            'managerId' => 'required|exists:users,id',
            'initialBalance' => 'required|numeric',
        ], [
            'name.required' => 'Название клиента обязательно',
            'name.max' => 'Название клиента не может быть длиннее 255 символов',
            'inn.required' => 'ИНН клиента обязателен',
            'inn.regex' => 'Некорректный формат ИНН',
            'inn.unique' => 'Данный ИНН уже используется',
            'managerId.required' => 'Выберите менеджера',
            'managerId.exists' => 'Менеджер не найден',
            'initialBalance.required' => 'Начальная статистика взаиморасчетов обязательна',
            'initialBalance.numeric' => 'Начальная статистика взаиморасчетов должна быть числом',
        ]);

        if ($this->id === null) {
            $createCommand->handle(new CreateClientCommand(
                name: $this->name,
                inn: $this->inn,
                initialBalance: $this->initialBalance,
                managerId: $this->managerId,
            ));
        } else {
            $updateCommand->handle(new UpdateClientCommand(
                id: $this->id,
                name: $this->name,
                inn: $this->inn,
                initialBalance: $this->initialBalance,
                managerId: $this->managerId,
            ));
        }

        $this->dispatch('modal-hide', name: 'client-modal');
        $this->dispatch('client-saved');
    }

    public function deleteClient(ClientService $clientService): void
    {
        $this->deleteError = null;

        if (! $this->canDeleteClient) {
            throw UnauthorizedException::forPermissions(
                SystemSettingsSectionPermissions::fullPermissionNames(
                    PermissionGroup::CLIENTS_AND_PROJECTS
                )
            );
        }

        $clientId = (int) $this->id;

        if ($clientService->hasProjects($clientId)) {
            $this->deleteError = 'Нельзя удалить клиента: у него есть клиенто-проекты.';

            return;
        }

        if ($clientService->hasPayments($clientId)) {
            $this->deleteError = 'Нельзя удалить клиента: у него есть платежи.';

            return;
        }

        if ($clientService->hasReports($clientId)) {
            $this->deleteError = 'Нельзя удалить клиента: на него ссылаются отчеты.';

            return;
        }

        try {
            $clientService->delete($clientId);
        } catch (QueryException) {
            $this->deleteError = 'Нельзя удалить клиента: на него ссылаются отчеты.';

            return;
        }

        $this->reset();
        $this->deleteError = null;
        $this->dispatch('modal-hide', name: 'client-modal');
        $this->dispatch('client-saved');
    }
};
