<?php

use App\Enums\FeeType;
use App\Services\UserService;
use App\Support\ClientsAndProjectsPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Src\Application\Clients\Create\CreateClientCommand;
use Src\Application\Clients\Create\CreateClientCommandHandler;
use Src\Application\Clients\Update\UpdateClientCommand;
use Src\Application\Clients\Update\UpdateClientCommandHandler;
use Src\Domain\Clients\ClientRepositoryInterface;

new class extends Component
{
    public ?int $id = null;
    public string $name;
    public string $inn;
    public int $managerId;
    public ?float $initialBalance = null;
    public string $adFeeType = 'three_percent';
    public ?string $adFeeChangedAt = null;

    /** Поля окна на момент открытия: кнопки показываем, только если есть изменения */
    public array $snapshot = [];

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
        $this->takeSnapshot();
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

        $client = app(ClientRepositoryInterface::class)->findById($id);
        $this->adFeeType = ($client->chargesAdFee() ? FeeType::THREE_PERCENT : FeeType::NONE)->value;
        $this->adFeeChangedAt = $client->getAdFeeChangedAt()?->format('Y-m-d');
        $this->takeSnapshot();

        $this->dispatch('modal-show', name: 'client-modal');
    }

    private function takeSnapshot(): void
    {
        $this->snapshot = [
            'name' => $this->name ?? '',
            'inn' => $this->inn ?? '',
            'managerId' => $this->managerId ?? null,
            'initialBalance' => $this->initialBalance,
            'adFeeType' => $this->adFeeType,
            'adFeeChangedAt' => $this->adFeeChangedAt,
        ];
    }

    #[Computed]
    public function canEdit(): bool
    {
        return ClientsAndProjectsPermissions::userCanEdit(Auth::user());
    }

    #[Computed]
    public function modalTitle()
    {
        return $this->id === null ? 'Создание клиента' : 'Редактирование клиента';
    }

    #[Computed]
    public function confirmButtonLabel()
    {
        return $this->id === null ? 'Создать клиента' : 'Сохранить';
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

    #[Computed]
    public function adFeeTypeOptions(): array
    {
        return array_map(
            fn (FeeType $type) => ['label' => $type->label(), 'value' => $type->value],
            FeeType::cases()
        );
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
                'unique:clients,inn,' . ($this->id ?: 'null')
            ],
            'managerId' => 'required|exists:users,id',
            'initialBalance' => 'nullable|numeric',
            'adFeeType' => ['required', Rule::enum(FeeType::class)],
            'adFeeChangedAt' => 'required_if:adFeeType,'.FeeType::NONE->value.'|nullable|date|before_or_equal:today',
        ], [
            'name.required' => 'Название клиента обязательно',
            'name.max' => 'Название клиента не может быть длиннее 255 символов',
            'inn.required' => 'ИНН клиента обязателен',
            'inn.regex' => 'Некорректный формат ИНН',
            'inn.unique' => 'Данный ИНН уже используется',
            'managerId.required' => 'Выберите менеджера',
            'managerId.exists' => 'Менеджер не найден',
            'initialBalance.numeric' => 'Начальная статистика взаиморасчетов должна быть числом',
            'adFeeChangedAt.required_if' => 'Укажите дату изменения расчета сбора',
            'adFeeChangedAt.before_or_equal' => 'Дата изменения расчета сбора не может быть в будущем',
        ]);

        $chargesAdFee = $this->adFeeType === FeeType::THREE_PERCENT->value;
        $adFeeChangedAt = $chargesAdFee || ! $this->adFeeChangedAt
            ? null
            : new DateTimeImmutable($this->adFeeChangedAt);

        if ($this->id === null) {
            $createCommand->handle(new CreateClientCommand(
                name: $this->name,
                inn: $this->inn,
                initialBalance: $this->initialBalance ?? 0.0,
                managerId: $this->managerId,
                chargesAdFee: $chargesAdFee,
                adFeeChangedAt: $adFeeChangedAt,
            ));
        } else {
            $updateCommand->handle(new UpdateClientCommand(
                id: $this->id,
                name: $this->name,
                inn: $this->inn,
                initialBalance: $this->initialBalance ?? 0.0,
                managerId: $this->managerId,
                chargesAdFee: $chargesAdFee,
                adFeeChangedAt: $adFeeChangedAt,
            ));
        }

        $this->dispatch('modal-hide', name: 'client-modal');
        $this->dispatch('client-saved');
    }
};
