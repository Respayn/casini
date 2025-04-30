<?php

namespace App\Enums;

enum CallibriSiteStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';
    case DELETED = 'DELETED';
    case PENDING = 'PENDING';
}
