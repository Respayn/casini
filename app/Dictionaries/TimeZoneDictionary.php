<?php

namespace App\Dictionaries;

class TimeZoneDictionary
{
    public static function list(): array
    {
        return [
            ['identifier' => 'Europe/Kaliningrad',   'offset' => '+02:00', 'name' => 'Калининград',   'label' => '(UTC+02:00) Калининград (МСК -1)'],
            ['identifier' => 'Europe/Moscow',        'offset' => '+03:00', 'name' => 'Москва',        'label' => '(UTC+03:00) Москва (МСК)'],
            ['identifier' => 'Europe/Samara',        'offset' => '+04:00', 'name' => 'Самара',        'label' => '(UTC+04:00) Самара (МСК +1)'],
            ['identifier' => 'Asia/Yekaterinburg',   'offset' => '+05:00', 'name' => 'Екатеринбург',  'label' => '(UTC+05:00) Екатеринбург (МСК +2)'],
            ['identifier' => 'Asia/Omsk',            'offset' => '+06:00', 'name' => 'Омск',          'label' => '(UTC+06:00) Омск (МСК +3)'],
            ['identifier' => 'Asia/Krasnoyarsk',     'offset' => '+07:00', 'name' => 'Красноярск',    'label' => '(UTC+07:00) Красноярск (МСК +4)'],
            ['identifier' => 'Asia/Irkutsk',         'offset' => '+08:00', 'name' => 'Иркутск',       'label' => '(UTC+08:00) Иркутск (МСК +5)'],
            ['identifier' => 'Asia/Yakutsk',         'offset' => '+09:00', 'name' => 'Якутск',        'label' => '(UTC+09:00) Якутск (МСК +6)'],
            ['identifier' => 'Asia/Vladivostok',     'offset' => '+10:00', 'name' => 'Владивосток',   'label' => '(UTC+10:00) Владивосток (МСК +7)'],
            ['identifier' => 'Asia/Sakhalin',        'offset' => '+11:00', 'name' => 'Южно-Сахалинск','label' => '(UTC+11:00) Южно-Сахалинск (МСК +8)'],
            ['identifier' => 'Asia/Magadan',         'offset' => '+11:00', 'name' => 'Магадан',       'label' => '(UTC+11:00) Магадан (МСК +8)'],
            ['identifier' => 'Asia/Kamchatka',       'offset' => '+12:00', 'name' => 'Камчатка',      'label' => '(UTC+12:00) Камчатка (МСК +9)'],
        ];
    }

    /**
     * Вывод для select
     *
     * @return array
     */
    public static function optionsForSelect(): array
    {
        return collect(self::list())
            ->map(fn($tz) => ['label' => $tz['label'], 'value' => $tz['identifier']])
            ->toArray();
    }

    /**
     * Поиск по идентификатору
     *
     * @param string $id
     * @return string[]|null
     */
    public static function byIdentifier(string $id): ?array
    {
        foreach (self::list() as $tz) {
            if ($tz['identifier'] === $id) return $tz;
        }
        return null;
    }
}
