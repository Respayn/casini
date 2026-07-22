<?php

namespace App\Enums;

enum AdvertisingSystem: string
{
    case Yandex = 'Яндекс';
    case Google = 'Google';
    case Vkontakte = 'Вконтакте';
    case YandexGeomedia = 'Яндекс Геомедийная реклама';
    case YandexMaps = 'Яндекс Карты';
    case YandexMedia = 'Яндекс медийная реклама';
    case YandexBusiness = 'Яндекс Бизнес';
    case VkontakteMedia = 'Вконтакте медийная реклама';
    case Avito = 'Авито';
    case AvitoMedia = 'Авито медийная реклама';
    case MyTarget = 'myTarget';
    case Other = 'Прочее';
}
