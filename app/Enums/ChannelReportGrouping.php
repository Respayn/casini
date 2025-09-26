<?php

namespace App\Enums;

enum ChannelReportGrouping: string
{
    case NONE = 'none';
    case ROLE = 'role';
    case CLIENTS = 'clients';
    case PROJECT_TYPE = 'project_type';
    case TOOLS = 'tools';
}
