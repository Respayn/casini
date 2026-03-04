<?php

namespace App\Services;

use Carbon\Carbon;
use Src\Infrastructure\Persistence\ReviseRepository;

class ReviseService
{
    private ReviseRepository $reviseRepository;

    public function __construct(ReviseRepository $reviseRepository)
    {
        $this->reviseRepository = $reviseRepository;
    }

    public function getReviseData(Carbon $periodFrom, Carbon $periodTo, $clientId = null, $managerId = null, $fetchFromDirect = false, $channelId = null) {
        return $this->reviseRepository->getReviseData($periodFrom, $periodTo, $clientId, $managerId, $fetchFromDirect, $channelId);
    }
}
