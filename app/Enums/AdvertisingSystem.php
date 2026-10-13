<?php

namespace App\Enums;

enum AdvertisingSystem: string
{
    case Yandex = 'Яндекс Директ';
    case Vkontakte = 'Вконтакте';
    case Google = 'Google Ads';
    case YandexGeomedia = 'Яндекс геомедийная реклама';
    case YandexMaps = 'Яндекс Карты';
    case YandexMedia = 'Яндекс медийная реклама';
    case YandexBusiness = 'Яндекс Бизнес';
    case VkontakteMedia = 'Вконтакте медийная реклама';
    case Avito = 'Авито';
    case AvitoMedia = 'Авито медийная реклама';
    case MyTarget = 'myTarget';
    case Other = 'Прочее';

    public function label(): string
    {
        return $this->chargesAdFee() ? $this->value : $this->value.' (сбор 0%)';
    }

    /**
     * Сбор 3% с пополнения кабинета; для Google Ads не удерживается при любой настройке клиента.
     */
    public function chargesAdFee(): bool
    {
        return $this !== self::Google;
    }
}
