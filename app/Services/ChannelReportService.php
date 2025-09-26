<?php

namespace App\Services;

use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use Illuminate\Support\Collection;

class ChannelReportService
{
    public function getReportData(ChannelReportQueryData $query): TableReportData
    {
        $reportData = new TableReportData();
        $groupData = new TableReportGroupData();
        $groupData->rows = new Collection([
            new Collection([
                'department' => 1,
                'login' => 'Логин',
            ]),
            new Collection(['manager' => 'манагер']),
        ]);

        $reportData->groups = new Collection([$groupData]);

        return $reportData;
    }
}
