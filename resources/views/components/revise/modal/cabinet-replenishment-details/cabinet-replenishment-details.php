<?php

use App\Models\Client;
use App\Models\Channel;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Client $client;
    public string $month;
    public $channelId;
    public $managerId;

    public $payments;

    public function mount(Client $client, string $month, $channelId = null, $managerId = null)
    {
        $this->client = $client;
        $this->channelId = $channelId;
        $this->managerId = $managerId;
        $this->month = $month;
    }

    #[Computed]
    public function payments()
    {
        $defaultChannel = Channel::getDefault();
        $monthObj = Carbon::parse($this->month);
        $channelId = $this->channelId;
        $managerId = $this->managerId;

        return $this->client->cabinetReplenishments()
            ->whereMonth('received_date', $monthObj->month)
            ->whereYear('received_date', $monthObj->year)
            ->with(['operations' => function ($query) use ($defaultChannel, $channelId, $managerId) {
                $query->where('cabinet_top_up_amount', '>', 0);

                if ($channelId) {
                    if ($channelId === $defaultChannel->id) {
                        $query->where(function ($q) use ($channelId) {
                            $q->whereNull('channel_id')->orWhere('channel_id', $channelId);
                        });
                    } else {
                        $query->where('channel_id', $channelId);
                    }
                } else {
                    $query->whereNull('channel_id');
                }

                if ($managerId) {
                    $query->where('manager_id', $managerId);
                }
            }])
            ->get();
    }
};
