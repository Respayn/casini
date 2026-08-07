<?php

namespace App\Services\IntegrationSync;

use App\Services\Channels\ChannelDirectApiThrottle;

/**
 * Общий лимит ручных запросов к API интеграций.
 * Реализация — ChannelDirectApiThrottle (историческое имя).
 */
class IntegrationApiThrottle extends ChannelDirectApiThrottle
{
}
