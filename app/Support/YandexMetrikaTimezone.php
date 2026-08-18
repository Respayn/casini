<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeZone;
use Throwable;

class YandexMetrikaTimezone
{
    /**
     * Смещение пояса для параметра timezone Reporting API (±hh:mm).
     * Пустой или неизвестный IANA-пояс не ломает запрос: возвращаем null.
     */
    public static function offsetFor(?string $iana, ?Carbon $at = null): ?string
    {
        $iana = trim((string) $iana);
        if ($iana === '') {
            return null;
        }

        try {
            new DateTimeZone($iana);
        } catch (Throwable) {
            return null;
        }

        return ($at ?? Carbon::now())->copy()->timezone($iana)->format('P');
    }

    /**
     * Смещение агентства для API, только если оно отличается от пояса счётчика.
     * Если пояс счётчика неизвестен или смещения равны — null (API сам возьмёт пояс счётчика).
     */
    public static function offsetIfDiffers(?string $agencyIana, ?string $counterIana, ?Carbon $at = null): ?string
    {
        $agencyOffset = self::offsetFor($agencyIana, $at);
        if ($agencyOffset === null) {
            return null;
        }

        $counterOffset = self::offsetFor($counterIana, $at);
        if ($counterOffset === null || $agencyOffset === $counterOffset) {
            return null;
        }

        return $agencyOffset;
    }
}
