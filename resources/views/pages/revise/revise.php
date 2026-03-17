<?php

namespace App\Livewire\Revise;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Revise\GetData\ReviseGetDataCommand;
use Src\Application\Revise\GetData\ReviseGetDataCommandHandler;
use App\Data\Revise\ReviseQueryData;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Channel;
use App\Models\User;

new #[Title('Casini - Сверка по рекламным бюджетам')]
class extends Component
{
    public Carbon $dateFrom;
    public Carbon $dateTo;
    public ?int $managerId = null;
    public ?int $channelId = null;
    public ?int $clientId = null;
    public bool $fetchFromDirect;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfMonth();
        $this->dateTo = Carbon::now()->endOfMonth();
        $this->clientId = null;
        $this->managerId = null;
        $this->channelId = null;
        $this->fetchFromDirect = false;
    }

    #[Computed]
    public function employeesData(): Collection
    {
        $periodFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $periodTo   = Carbon::parse($this->dateTo)->endOfDay();

        return app(ReviseGetDataCommandHandler::class)->handle(
            new ReviseGetDataCommand(
                dateFrom: $periodFrom,
                dateTo: $periodTo,
                clientId: $this->clientId,
                managerId: $this->managerId,
                channelId: $this->channelId,
                fetchFromDirect: $this->fetchFromDirect,
            )
        );
    }
    
    #[Computed]
    public function clients(): array
    {
        return Client::all()
            ->map(fn ($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])
            ->prepend([
                'value' => null,
                'label' => 'Все',
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function managers(): array
    {
        return User::role([Role::MANAGER, Role::MANAGER_DEPARTMENT_HEAD])
            ->get()
            ->map(fn ($item) => [
                'value' => $item->id,
                'label' => $item->fullName,
            ])
            ->prepend([
                'value' => null,
                'label' => 'Все',
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function channels(): array
    {
        return Channel::all()
            ->map(fn ($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])
            ->prepend([
                'value' => null,
                'label' => 'Все',
            ])
            ->values()
            ->toArray();
    }
}
