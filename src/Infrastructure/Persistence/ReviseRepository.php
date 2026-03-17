<?php

namespace Src\Infrastructure\Persistence;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Src\Domain\Revise\ReviseRepositoryInterface;
use App\Services\YandexDirectService;
use App\Services\WorkActService;
use App\Enums\Role;
use App\Models\User;
use App\Models\PaymentOperation;
use App\Models\Channel;
use App\Models\Client;
use App\Models\Project;
use App\Models\PerformedWorkAct;
use App\Data\Revise\ReviseData;
use App\Data\Revise\EmployeeData;
use App\Data\Revise\ClientData;
use App\Data\Revise\ChannelData;
use Illuminate\Support\Collection as SupportCollection;

class ReviseRepository implements ReviseRepositoryInterface
{
    private YandexDirectService $yandexDirectService;
    private WorkActService $workActService;
    private Channel $defaultChannel;
    private Channel $yandexDirectChannel;

    public function __construct(YandexDirectService $yandexDirectService, WorkActService $workActService)
    {
        $this->yandexDirectService = $yandexDirectService;
        $this->yandexDirectService->setupClient(
            config('services.yandex_direct.test_token'),
            config('services.yandex_direct.test_client_login')
        );
        $this->workActService = new WorkActService();
        $this->defaultChannel = Channel::getDefault();
        $this->yandexDirectChannel = Channel::where('name', 'Яндекс Директ')->first();
    }

    public function GetData(
        Carbon $periodFrom,
        Carbon $periodTo,
        ?int $clientId = null,
        ?int $managerId = null,
        bool $fetchFromDirect = false,
        ?int $channelId = null
    ): Collection {
        $formattedPeriodFrom = $periodFrom->format('Y-m-d');
        $formattedPeriodTo = $periodTo->format('Y-m-d');

        $managers = $this->getManagers($managerId, $clientId, $formattedPeriodFrom, $formattedPeriodTo);

        $employeesData = [];
        $defaultChannel = Channel::getDefault();

        foreach ($managers as $manager) {
            $employeeData = $this->createEmployeeData($manager, $formattedPeriodFrom, $formattedPeriodTo);
            $managerPaymentOperations = $manager->paymentOperations
                ->when(!is_null($channelId), function (Collection $collection) use ($defaultChannel, $channelId) {
                    if ($channelId == $defaultChannel->id) {
                        return $collection->whereIn('channel_id', [$channelId, null]);
                    } else {
                        return $collection->where('channel_id', $channelId);
                    }
                });

            // Сортируем по проектам, чтобы при генерации структуры данных, если включена галочка "показать расходы в директе"
            // корректно подтягивался проект для съема данных
            foreach ($managerPaymentOperations->sortByDesc('project') as $operation) {
                $this->processOperation($operation, $employeeData);
            }

            $employeesData[] = $employeeData;
        }

        $this->processWorkActs($employeesData, $periodFrom, $periodTo);

        if ($fetchFromDirect && ($channelId === null || $channelId === $this->yandexDirectChannel->id)) {
            $employeesData = $this->processDirectSpendings($employeesData, $periodFrom, $periodTo, $clientId);
        }

        $employeesData = collect($employeesData);
        $employeesData = $this->filterByChannel($employeesData, $channelId);

        // Фильтрация пустых данных
        foreach ($employeesData as $employeeIndex => $employee) {
            foreach ($employee->clients as $clientIndex => $client) {
                foreach ($client->channels as $channelIndex => $channel) {
                    if (empty($channel->revises)) {
                        unset($employeesData[$employeeIndex]->clients[$clientIndex]->channels[$channelIndex]);
                    }
                }

                if (empty($client->channels)) {
                    unset($employeesData[$employeeIndex]->clients[$clientIndex]);
                }
            }

            if (empty($employee->clients)) {
                unset($employeesData[$employeeIndex]);
            }
        }

        foreach ($employeesData as $employee) {
            foreach ($employee->clients as $client) {
                foreach ($client->channels as $channel) {
                    usort($channel->revises, function ($reviseA, $reviseB) {
                        return $reviseA->date->gt($reviseB->date);
                    });
                }
            }
        }

        return $employeesData;
    }

    private function processWorkActs(&$employeesData, Carbon $periodFrom, Carbon $periodTo): void
    {
        $workActs = $this->workActService->getWorkActsByDate($periodFrom, $periodTo);
        foreach ($employeesData as &$employeeData) {
            foreach ($employeeData->clients as $client) {
                // Выбираем акты клиента
                $clientWorkActs = $workActs->filter(function (PerformedWorkAct $value) use ($client) {
                    return $value->client?->id === $client->id;
                });

                // Обрабатываем
                foreach ($clientWorkActs as $workAct) {
                    $workActDate = $workAct->creation_date->format('Y-m');

                    foreach ($workAct->items as $item) {
                        // Определить канал операции
                        $channelType = $this->workActService->determineChannelType($item);
                        $channelTypeId = $channelType->id;
                        $channelTypeName = $channelType->name;

                        $clientKey = $client->id;

                        // Найти канал в результатах
                        if (!isset($employeeData->clients[$clientKey]->channels[$channelTypeId])) {
                            $employeeData->clients[$clientKey]->channels[$channelTypeId] = new ChannelData();
                            $employeeData->clients[$clientKey]->channels[$channelTypeId]->id = $channelTypeId;
                            $employeeData->clients[$clientKey]->channels[$channelTypeId]->name = $channelTypeName;
                        }

                        if (!isset($employeeData->clients[$clientKey]->channels[$channelTypeId]->revises[$workActDate])) {
                            $employeeData->clients[$clientKey]->channels[$channelTypeId]->revises[$workActDate] = new ReviseData();
                            $employeeData->clients[$clientKey]->channels[$channelTypeId]->revises[$workActDate]->date = $workAct->creation_date;
                        }

                        // Суммируем по дате
                        $employeeData->clients[$clientKey]->channels[$channelTypeId]->revises[$workActDate]->workActsSum += $item->price;

                        // Суммируем по каналу
                        $employeeData->clients[$clientKey]->channels[$channelTypeId]->workActsSum += $item->price;

                        // Суммируем по сотруднику
                        $employeeData->workActsSum += $item->price;
                    }
                }

                // Удаляем обработанные акты
                $workActs = $workActs->reject(function (PerformedWorkAct $value) use ($client) {
                    return $value->client?->id === $client->id;
                });
            }
        }
    }

    private function filterByChannel(SupportCollection $employeesData, ?int $channelId): SupportCollection
    {
        if ($channelId) {
            $employeesData->each(function ($employee) use ($channelId) {
                foreach ($employee->clients as $clientKey => $client) {
                    foreach ($client->channels as $channelKey => $channel) {
                        // If the channel ID does not match, remove it
                        if ($channel->id !== $channelId) {
                            unset($employee->clients[$clientKey]->channels[$channelKey]);
                        }
                    }

                    // If the client has no remaining channels, remove the client
                    if (empty($employee->clients[$clientKey]->channels)) {
                        unset($employee->clients[$clientKey]);
                    }
                }
            });

            // Remove employees who have no remaining clients
            $employeesData = $employeesData->filter(function ($employee) {
                return !empty($employee->clients);
            });
        }

        return $employeesData;
    }

    private function getManagers(?int $managerId, ?int $clientId, string $formattedPeriodFrom, string $formattedPeriodTo): Collection
    {
        $managers = User::role([Role::MANAGER, Role::MANAGER_DEPARTMENT_HEAD])
            ->with([
                'paymentOperations' => [
                    'clientProject',
                    'channel'
                ]
            ]);

        if ($managerId) {
            $managers = $managers->where('id', $managerId);
        }

        // подтягиваем платежные операции
        $managers = $managers->with('paymentOperations', function ($query) use ($clientId, $formattedPeriodFrom, $formattedPeriodTo) {
            $query->withWhereHas('payment', function ($query) use ($clientId, $formattedPeriodFrom, $formattedPeriodTo) {
                $query->whereBetween('received_date', [$formattedPeriodFrom, $formattedPeriodTo]);
                if ($clientId) {
                    $query->where('client_id', $clientId);
                }
            });
        });

        return $managers->get();
    }

    private function createEmployeeData(User $manager, string $formattedPeriodFrom, string $formattedPeriodTo): EmployeeData
    {
        $employeeData = new EmployeeData();
        $employeeData->id = $manager->id;
        $employeeData->name = $manager->full_name;
        $employeeData->periodFrom = $formattedPeriodFrom;
        $employeeData->periodTo = $formattedPeriodTo;
        return $employeeData;
    }

    private function processOperation(
        PaymentOperation $operation,
        EmployeeData $employeeData
    ): void {
        $clientKey = $operation->payment->client_id;
        $reviseKey = $operation->payment->received_date->format('Y-m');
        $channelKey = $operation->channel_id ?? $this->defaultChannel->id;

        if (!isset($employeeData->clients[$clientKey])) {
            $employeeData->clients[$clientKey] = new ClientData();
            $employeeData->clients[$clientKey]->id = $operation->payment->client->id;
            $employeeData->clients[$clientKey]->name = $operation->payment->client->name;
            $employeeData->clients[$clientKey]->initialBalance = $operation->payment->client->initial_balance;
        }

        if (!isset($employeeData->clients[$clientKey]->channels[$channelKey])) {
            $employeeData->clients[$clientKey]->channels[$channelKey] = new ChannelData();
            $employeeData->clients[$clientKey]->channels[$channelKey]->id = $operation->channel?->id ?? $this->defaultChannel->id;
            $employeeData->clients[$clientKey]->channels[$channelKey]->name = $operation->channel?->name ?? $this->defaultChannel->name;
        }

        if (!isset($employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey])) {
            $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey] = new ReviseData();
            $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->date = $operation->payment->received_date;
        }

        if ($operation->credit_amount != 0) {
            $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->creditCount++;
        }

        if ($operation->bank_received_amount != 0) {
            $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->incomeCount++;
        }

        if ($operation->cabinet_top_up_amount != 0) {
            $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->cabinetReplenishmentCount++;
        }

        $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->income += $operation->bank_received_amount;
        $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->credit += $operation->credit_amount;
        $employeeData->clients[$clientKey]->channels[$channelKey]->revises[$reviseKey]->cabinetReplenishment += $operation->cabinet_top_up_amount;

        $employeeData->clients[$clientKey]->channels[$channelKey]->income += $operation->bank_received_amount;
        $employeeData->clients[$clientKey]->channels[$channelKey]->credit += $operation->credit_amount;
        $employeeData->clients[$clientKey]->channels[$channelKey]->cabinetReplenishment += $operation->cabinet_top_up_amount;

        $employeeData->income += $operation->bank_received_amount;
        $employeeData->credit += $operation->credit_amount;
        $employeeData->cabinetReplenishment += $operation->cabinet_top_up_amount;
    }

    private function processDirectSpendings($employeesData, Carbon $periodFrom, Carbon $periodTo, $clientId): mixed
    {
        foreach ($employeesData as $employeeKey => $employee) {
            $clients = Client::where('manager_id', $employee->id)
                ->when($clientId, function ($query) use ($clientId) {
                    $query->where('id', $clientId);
                })
                ->get();

            foreach ($clients as $client) {
                if (!isset($employee->clients[$client->id])) {
                    $employeesData[$employeeKey]->clients[$client->id] = new ClientData();
                    $employeesData[$employeeKey]->clients[$client->id]->id = $client->id;
                    $employeesData[$employeeKey]->clients[$client->id]->name = $client->name;
                    $employeesData[$employeeKey]->clients[$client->id]->initialBalance = $client->initial_balance;
                }
            }

            foreach ($employee->clients as $clientKey => $client) {
                if (!isset($client->channels[$this->yandexDirectChannel->id])) {
                    $directChannel = new ChannelData();
                    $directChannel->id = $this->yandexDirectChannel->id;
                    $directChannel->name = $this->yandexDirectChannel->name;

                    $employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id] = $directChannel;
                }

                $projects = Project::whereClientId($client->id)->get();
                foreach ($projects as $project) {
                    $this->yandexDirectService->clientLogin = $project->integrations->where('channel_id', $this->yandexDirectChannel->id)->where('is_enabled', true)->first()->client_login ?? null;
                    $report = $this->yandexDirectService->getProjectExpensesByMonth($periodFrom, $periodTo);

                    if (!isset($report['error'])) {
                        foreach ($report as $reportRow) {
                            $reportRowMonth = new Carbon($reportRow['Month']);

                            if (!isset($client->channels[$this->yandexDirectChannel->id]->revises[$reportRowMonth->format('Y-m')])) {
                                $employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id]->revises[$reportRowMonth->format('Y-m')] = new ReviseData();
                                $employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id]->revises[$reportRowMonth->format('Y-m')]->date = $reportRowMonth;
                            }

                            $employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id]->revises[$reportRowMonth->format('Y-m')]->outcome = $reportRow['Cost'];

                            if ($employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id]->outcome === '-') {
                                $employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id]->outcome = $reportRow['Cost'];
                            } else {
                                $employeesData[$employeeKey]->clients[$clientKey]->channels[$this->yandexDirectChannel->id]->outcome += $reportRow['Cost'];
                            }

                            if ($employeesData[$employeeKey]->outcome === '-') {
                                $employeesData[$employeeKey]->outcome = $reportRow['Cost'];
                            } else {
                                $employeesData[$employeeKey]->outcome += $reportRow['Cost'];
                            }
                        }
                    }
                }
            }
        }

        return $employeesData;
    }
}
