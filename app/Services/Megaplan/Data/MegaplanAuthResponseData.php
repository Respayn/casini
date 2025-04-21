<?php

namespace App\Services\Megaplan\Data;

class MegaplanAuthResponseData extends MegaplanResponseData
{
    public string $accessId;
    public string $secretKey;
    public int $userId;
    public int $employeeId;
}
