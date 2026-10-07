<?php

namespace App\Services\Bitrix24;

use RuntimeException;

/**
 * Ошибка REST Битрикс24. Текст без адреса вебхука: его можно показывать в UI и писать в лог.
 */
class Bitrix24ApiException extends RuntimeException {}
