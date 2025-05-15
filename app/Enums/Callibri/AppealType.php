<?php

namespace App\Enums\Callibri;

enum AppealType: string
{
    case CALLS = 'calls';
    case CHATS = 'chats';
    case EMAILS = 'emails';
    case REQUESTS = 'requests';
}
