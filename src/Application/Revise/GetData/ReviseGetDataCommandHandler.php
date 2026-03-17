<?php

namespace Src\Application\Revise\GetData;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Src\Domain\Revise\ReviseRepositoryInterface;

class ReviseGetDataCommandHandler
{
    public function __construct(
        private readonly ReviseRepositoryInterface $reviseRepository,
    ) {}

    public function handle(ReviseGetDataCommand $command): Collection
    {
        //получаем данные из репозитория (инфраструктурный слой)
        $result = $this->reviseRepository->GetData(
            $command->dateFrom,
            $command->dateTo,
            $command->clientId,
            $command->managerId,
            $command->fetchFromDirect,
            $command->channelId
        );

        //маппим данные и возвращаем коллекцию DTO
        return $result->map(fn($row) => new ReviseGetDataDto(
            id: data_get($row, 'id'),
            name: (string) data_get($row, 'name', ''),
            clients: data_get($row, 'clients', []),
            date: Carbon::parse(data_get($row, 'date')),
            income: (float) data_get($row, 'income', 0),
            outcome: data_get($row, 'outcome', '-'),
            credit: (float) data_get($row, 'credit', 0),
            creditCount: (int) data_get($row, 'creditCount', 0),
            incomeCount: (int) data_get($row, 'incomeCount', 0),
            cabinetReplenishment: (float) data_get($row, 'cabinetReplenishment', 0),
            cabinetReplenishmentCount: (int) data_get($row, 'cabinetReplenishmentCount', 0),
            workActsSum: (float) data_get($row, 'workActsSum', 0),
            workActs: (array) data_get($row, 'workActs', []),
        ))->values();
    }
}
