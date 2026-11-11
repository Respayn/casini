<?php

namespace App\Enums;

enum SearchRegion: int
{
    // Страны
    case RUSSIA = 225;
    case UKRAINE = 187;
    case BELARUS = 149;
    case KAZAKHSTAN = 159;

    // Регионы
    case ARKHANGELSK = 20;
    case ASTRAKHAN = 37;
    case BARNAUL = 197;
    case BELGOROD = 4;
    case BLAGOVESHCHENSK = 77;
    case BRYANSK = 191;
    case VELIKY_NOVGOROD = 24;
    case VLADIVOSTOK = 75;
    case VLADIKAVKAZ = 33;
    case VLADIMIR = 192;
    case VOLGOGRAD = 38;
    case VOLOGDA = 21;
    case VORONEZH = 193;
    case GROZNY = 1106;
    case YEKATERINBURG = 54;
    case IVANOVO = 5;
    case IRKUTSK = 63;
    case YOSHKAR_OLA = 41;
    case KAZAN = 43;
    case KALININGRAD = 22;
    case KEMEROVO = 64;
    case KOSTROMA = 7;
    case KRASNODAR = 35;
    case KRASNOYARSK = 62;
    case KURGAN = 53;
    case KURSK = 8;
    case LIPETSK = 9;
    case MAKHACHKALA = 28;
    case MOSCOW_AND_MOSCOW_REGION = 1;
    case MOSCOW = 213;
    case MURMANSK = 23;
    case NAZRAN = 1092;
    case NALCHIK = 30;
    case NIZHNY_NOVGOROD = 47;
    case NOVOSIBIRSK = 65;
    case OMSK = 66;
    case ORYOL = 10;
    case ORENBURG = 48;
    case PENZA = 49;
    case PERM = 50;
    case PSKOV = 25;
    case ROSTOV_ON_DON = 39;
    case RYAZAN = 11;
    case SAMARA = 51;
    case SAINT_PETERSBURG = 2;
    case SARANSK = 42;
    case SMOLENSK = 12;
    case SOCHI = 239;
    case STAVROPOL = 36;
    case SURGUT = 973;
    case TAMBOV = 13;
    case TVER = 14;
    case TOMSK = 67;
    case TULA = 15;
    case ULYANOVSK = 195;
    case UFA = 172;
    case KHABAROVSK = 76;
    case CHEBOKSARY = 45;
    case CHELYABINSK = 56;
    case CHERKESSK = 1104;
    case YAROSLAVL = 16;

    public function label(): string
    {
        return match ($this) {
            self::RUSSIA => 'Россия',
            self::UKRAINE => 'Украина',
            self::BELARUS => 'Беларусь',
            self::KAZAKHSTAN => 'Казахстан',

            self::ARKHANGELSK => 'Архангельск',
            self::ASTRAKHAN => 'Астрахань',
            self::BARNAUL => 'Барнаул',
            self::BELGOROD => 'Белгород',
            self::BLAGOVESHCHENSK => 'Благовещенск',
            self::BRYANSK => 'Брянск',
            self::VELIKY_NOVGOROD => 'Великий Новгород',
            self::VLADIVOSTOK => 'Владивосток',
            self::VLADIKAVKAZ => 'Владикавказ',
            self::VLADIMIR => 'Владимир',
            self::VOLGOGRAD => 'Волгоград',
            self::VOLOGDA => 'Вологда',
            self::VORONEZH => 'Воронеж',
            self::GROZNY => 'Грозный',
            self::YEKATERINBURG => 'Екатеринбург',
            self::IVANOVO => 'Иваново',
            self::IRKUTSK => 'Иркутск',
            self::YOSHKAR_OLA => 'Йошкар-Ола',
            self::KAZAN => 'Казань',
            self::KALININGRAD => 'Калининград',
            self::KEMEROVO => 'Кемерово',
            self::KOSTROMA => 'Кострома',
            self::KRASNODAR => 'Краснодар',
            self::KRASNOYARSK => 'Красноярск',
            self::KURGAN => 'Курган',
            self::KURSK => 'Курск',
            self::LIPETSK => 'Липецк',
            self::MAKHACHKALA => 'Махачкала',
            self::MOSCOW_AND_MOSCOW_REGION => 'Москва и Московская область',
            self::MOSCOW => 'Москва',
            self::MURMANSK => 'Мурманск',
            self::NAZRAN => 'Назрань',
            self::NALCHIK => 'Нальчик',
            self::NIZHNY_NOVGOROD => 'Нижний Новгород',
            self::NOVOSIBIRSK => 'Новосибирск',
            self::OMSK => 'Омск',
            self::ORYOL => 'Орел',
            self::ORENBURG => 'Оренбург',
            self::PENZA => 'Пенза',
            self::PERM => 'Пермь',
            self::PSKOV => 'Псков',
            self::ROSTOV_ON_DON => 'Ростов-на-Дону',
            self::RYAZAN => 'Рязань',
            self::SAMARA => 'Самара',
            self::SAINT_PETERSBURG => 'Санкт-Петербург',
            self::SARANSK => 'Саранск',
            self::SMOLENSK => 'Смоленск',
            self::SOCHI => 'Сочи',
            self::STAVROPOL => 'Ставрополь',
            self::SURGUT => 'Сургут',
            self::TAMBOV => 'Тамбов',
            self::TVER => 'Тверь',
            self::TOMSK => 'Томск',
            self::TULA => 'Тула',
            self::ULYANOVSK => 'Ульяновск',
            self::UFA => 'Уфа',
            self::KHABAROVSK => 'Хабаровск',
            self::CHEBOKSARY => 'Чебоксары',
            self::CHELYABINSK => 'Челябинск',
            self::CHERKESSK => 'Черкесск',
            self::YAROSLAVL => 'Ярославль',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (SearchRegion $searchRegion) => ['label' => $searchRegion->label(), 'value' => $searchRegion->value],
            self::cases()
        );
    }

    /**
     * @param  list<mixed>  $codes
     */
    public static function labelsForCodes(array $codes): string
    {
        $labels = [];

        foreach ($codes as $code) {
            if (! is_numeric($code)) {
                continue;
            }

            $label = self::tryFrom((int) $code)?->label();
            if ($label === null) {
                continue;
            }

            $labels[$label] = $label;
        }

        return implode(', ', array_values($labels));
    }
}
