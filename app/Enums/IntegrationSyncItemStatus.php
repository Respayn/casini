<?php

namespace App\Enums;

enum IntegrationSyncItemStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';
}
