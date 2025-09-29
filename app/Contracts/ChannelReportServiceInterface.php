<?php

namespace App\Contracts;

use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportData;

interface ChannelReportServiceInterface
{
    public function getReportData(ChannelReportQueryData $query): TableReportData;
}
