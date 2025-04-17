<?php

namespace App\Data\Integrations;

use Illuminate\Support\Collection;

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

    public static function fromSettings(Collection $settings): self
    {
        $data = new self();
        $data->host = $settings->get('host');
        $data->login = $settings->get('login');
        $data->password = $settings->get('password');
        $data->ticketNumber = $settings->get('ticket_number');
        $data->searchStringSuffix = $settings->get('search_string_suffix');
        $data->parseComments = $settings->get('parse_comments', false);
        return $data;
    }
}
