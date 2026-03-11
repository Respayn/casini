<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\Channel;
use App\Models\Client;
use App\Models\PerformedWorkAct;
use App\Models\PerformedWorkActItem;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class WorkActService
{
    public function isWorkActRelateToProject($workAct, array $project): bool
    {
        $normalizedProjectContractNumber = StringHelper::normalize($project['contract_number']);

        $additionalContractNumbers = collect(is_array($project['additional_contract_number']) ? $project['additional_contract_number'] : json_decode($project['additional_contract_number']));
        $additionalContractNumbersEmpty = $additionalContractNumbers->isEmpty();

        $isProjectContractNumbersEmpty = empty($normalizedProjectContractNumber) && $additionalContractNumbersEmpty;

        if ($isProjectContractNumbersEmpty)
            return false;

        $normalizedWorkActContractNumber = StringHelper::normalize($workAct->contract_number);
        $normalizedWorkActAdditionalContractNumber = StringHelper::normalize($workAct->customer_additional_number);
        $isActContractNumberEmpty = empty($normalizedWorkActContractNumber) && empty($normalizedWorkActAdditionalContractNumber);
        $processByInn = $isActContractNumberEmpty && ($project['inn'] == $workAct->customer_inn);

        if ($processByInn && $project['inn'] == $workAct->customer_inn) {
            return true;
        } 
        
        if (($normalizedProjectContractNumber == $normalizedWorkActContractNumber) &&
            ($additionalContractNumbersEmpty || $additionalContractNumbers->contains($normalizedWorkActAdditionalContractNumber))
        ) {
            return true;
        }

        return false;
    }

    public function determineChannelType(PerformedWorkActItem $workActItem): Channel
    {
        $channels = Channel::whereNotNull('search_string')->get();
        foreach ($channels as $channel) {
            if ($this->workActFromChannel($workActItem, $channel)) {
                return $channel;
            }
        }
        
        $defaultChannel = Channel::getDefault();
        return $defaultChannel;
    }

    public function workActFromChannel(PerformedWorkActItem $workActItem, Channel $channel): bool
    {
        return stripos($workActItem->name, $channel->search_string) !== false;
    }

    public function getWorkActsByClientAndChannel(int $clientId, Carbon $month, int $channelId): Collection
    {
        $client = Client::find($clientId);

        $workActs = $client->performedWorkActs()
            ->with('items')
            ->whereBetween('creation_date', [$month->startOfMonth()->format('Y-m-d'), $month->endOfMonth()->format('Y-m-d')])
            ->get();

        $channel = Channel::find($channelId);

        // Если выбран дефолтный канал, то нужно отсеять совпадения с другими каналами
        if ($channel->search_string == null) {
            $channels = Channel::whereNotNull('search_string')->get();
            $workActs = $workActs->filter(function ($workAct) use ($channels) {
                $workAct->items = $workAct->items->filter(function ($item) use ($channels) {
                    foreach ($channels as $channel) {
                        return stripos($item->name, $channel->search_string) === false;
                    }

                    return true;
                });
    
                return !$workAct->items->isEmpty();
            });
        } else {
            $workActs = $workActs->filter(function ($workAct) use ($channel) {
                $workAct->items = $workAct->items->filter(function ($item) use ($channel) {
                    return stripos($item->name, $channel->search_string) !== false;
                });
    
                return !$workAct->items->isEmpty();
            });
        }

        return $workActs;
    }

    // TODO: move to repository class
    public function getWorkActsByClient(int $clientId, Carbon $from, Carbon $to): Collection
    {
        $client = Client::find($clientId);

        return $client->performedWorkActs()
            ->with('items')
            ->whereBetween('creation_date', [$from->startOfMonth()->format('Y-m-d'), $to->endOfMonth()->format('Y-m-d')])
            ->get();
    }

    public function getWorkActsByDate(Carbon $from, Carbon $to): Collection
    {
        return PerformedWorkAct::with('items')
            ->with('client')
            ->whereBetween('creation_date', [$from->startOfMonth()->format('Y-m-d'), $to->endOfMonth()->format('Y-m-d')])
            ->get();
    }
}
