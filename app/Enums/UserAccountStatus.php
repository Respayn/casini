<?php

namespace App\Enums;

enum UserAccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case PendingEmail = 'pending_email';
}
