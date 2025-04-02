<?php

namespace App\Data\IntegrationSettings;

class MegaplanIntegrationSettingsData extends IntegrationSettingsData
{
    /** 
     * @var string Хост поддомена мегаплана, на который будут отправляться 
     * запросы 
     */
    public ?string $host;

    public ?string $login;
    public ?string $password;

    /**
     * @var string Номер корневого тикета
     */
    public string $ticketNumber;

    /**
     * @var string Суффикс для строки поиска тикетов
     */
    public string $searchStringSuffix;

    /**
     * @var bool Флаг, указывающий на то, нужно ли парсить комментарии тикетов
     * для получения информации о затраченном времени
     */
    public bool $parseComments = false;
}
