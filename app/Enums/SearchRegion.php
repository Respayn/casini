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
            static::RUSSIA => 'Россия',
            static::UKRAINE => 'Украина',
            static::BELARUS => 'Беларусь',
            static::KAZAKHSTAN => 'Казахстан',

            static::ARKHANGELSK => 'Архангельск',
            static::ASTRAKHAN => 'Астрахань',
            static::BARNAUL => 'Барнаул',
            static::BELGOROD => 'Белгород',
            static::BLAGOVESHCHENSK => 'Благовещенск',
            static::BRYANSK => 'Брянск',
            static::VELIKY_NOVGOROD => 'Великий Новгород',
            static::VLADIVOSTOK => 'Владивосток',
            static::VLADIKAVKAZ => 'Владикавказ',
            static::VLADIMIR => 'Владимир',
            static::VOLGOGRAD => 'Волгоград',
            static::VOLOGDA => 'Вологда',
            static::VORONEZH => 'Воронеж',
            static::GROZNY => 'Грозный',
            static::YEKATERINBURG => 'Екатеринбург',
            static::IVANOVO => 'Иваново',
            static::IRKUTSK => 'Иркутск',
            static::YOSHKAR_OLA => 'Йошкар-Ола',
            static::KAZAN => 'Казань',
            static::KALININGRAD => 'Калининград',
            static::KEMEROVO => 'Кемерово',
            static::KOSTROMA => 'Кострома',
            static::KRASNODAR => 'Краснодар',
            static::KRASNOYARSK => 'Красноярск',
            static::KURGAN => 'Курган',
            static::KURSK => 'Курск',
            static::LIPETSK => 'Липецк',
            static::MAKHACHKALA => 'Махачкала',
            static::MOSCOW_AND_MOSCOW_REGION => 'Москва и Московская область',
            static::MOSCOW => 'Москва',
            static::MURMANSK => 'Мурманск',
            static::NAZRAN => 'Назрань',
            static::NALCHIK => 'Нальчик',
            static::NIZHNY_NOVGOROD => 'Нижний Новгород',
            static::NOVOSIBIRSK => 'Новосибирск',
            static::OMSK => 'Омск',
            static::ORYOL => 'Орел',
            static::ORENBURG => 'Оренбург',
            static::PENZA => 'Пенза',
            static::PERM => 'Пермь',
            static::PSKOV => 'Псков',
            static::ROSTOV_ON_DON => 'Ростов-на-Дону',
            static::RYAZAN => 'Рязань',
            static::SAMARA => 'Самара',
            static::SAINT_PETERSBURG => 'Санкт-Петербург',
            static::SARANSK => 'Саранск',
            static::SMOLENSK => 'Смоленск',
            static::SOCHI => 'Сочи',
            static::STAVROPOL => 'Ставрополь',
            static::SURGUT => 'Сургут',
            static::TAMBOV => 'Тамбов',
            static::TVER => 'Тверь',
            static::TOMSK => 'Томск',
            static::TULA => 'Тула',
            static::ULYANOVSK => 'Ульяновск',
            static::UFA => 'Уфа',
            static::KHABAROVSK => 'Хабаровск',
            static::CHEBOKSARY => 'Чебоксары',
            static::CHELYABINSK => 'Челябинск',
            static::CHERKESSK => 'Черкесск',
            static::YAROSLAVL => 'Ярославль',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (SearchRegion $searchRegion) => ['label' => $searchRegion->label(), 'value' => $searchRegion->value],
            self::cases()
        );
    }
}
