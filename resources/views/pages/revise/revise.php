<?php

namespace App\Livewire\Statistics;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Revise\GetData\ReviseGetDataCommand;
use Src\Application\Revise\GetData\ReviseGetDataCommandHandler;

new #[Title('Casini - Сверка по рекламным бюджетам')]
class extends Component
{
    public string $dateFrom;
    public string $dateTo;

    public ?int $clientId = null;
    public ?int $managerId = null;
    public ?int $channelId = null;
    public bool $fetchFromDirect = false;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->endOfMonth()->toDateString();
    }

    #[Computed]
    public function employeesData(): Collection
    {
        $periodFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $periodTo   = Carbon::parse($this->dateTo)->endOfDay();

        // return app(ReviseGetDataCommandHandler::class)->handle(
        //     new ReviseGetDataCommand(
        //         dateFrom: $periodFrom,
        //         dateTo: $periodTo,
        //         clientId: $this->clientId,
        //         managerId: $this->managerId,
        //         fetchFromDirect: $this->fetchFromDirect,
        //         channelId: $this->channelId,
        //     )
        // );

        return app(ReviseGetDataCommandHandler::class)->handle(
            new ReviseGetDataCommand(
                dateFrom: Carbon::parse("10.01.2022")->endOfDay(),
                dateTo: Carbon::parse("10.01.2026")->endOfDay(),
            )
        );
    }
}
