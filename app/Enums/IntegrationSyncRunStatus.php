<?php

namespace App\Enums;

enum IntegrationSyncRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
