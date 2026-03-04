<?php

namespace Src\Domain\Revises;

use Carbon\Carbon;

interface ReviseRepositoryInterface
{
    /**
     * Получить сверку по бюджетам
     * @param Carbon $periodFrom
     * @param Carbon $periodTo
     * @param mixed $clientId
     * @param mixed $managerId
     * @param mixed $fetchFromDirect
     * @param mixed $channelId
     * @return void В разработке
     */
    public function getReviseData(Carbon $periodFrom, Carbon $periodTo, $clientId = null, $managerId = null, $fetchFromDirect = false, $channelId = null);
}
