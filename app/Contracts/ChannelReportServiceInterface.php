<?php

namespace App\Contracts;

use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportData;

interface ChannelReportServiceInterface
{
    public function getUserSettings(int $userId): ChannelReportQueryData;
    public function saveUserSettings(int $userId, ChannelReportQueryData $settings): void;
    public function getReportData(ChannelReportQueryData $query): TableReportData;
}
