<?php

use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
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

    private UserService $userService;

    public function boot(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[On('client-create')]
    public function onClientCreate()
    {
        $this->reset();
        $this->dispatch('modal-show', name: 'client-modal');
    }

    #[On('client-edit')]
    public function onClientEdit($id, $name, $inn, $initialBalance, $managerId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->inn = $inn;
        $this->initialBalance = $initialBalance;
        $this->managerId = $managerId;
        $this->dispatch('modal-show', name: 'client-modal');
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
            ->map(fn($manager) => [
                'label' => $this->formatManagerName($manager),
                'value' => $manager->id
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
        $this->validate([
            'name' => 'required|string|max:255',
            'inn' => [
                'required',
                'regex:/^\d{10,12}$/',
                'unique:clients,inn,' . ($this->id ?: 'null')
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
};
