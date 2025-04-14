<?php

namespace App\Parsers;

class YandexDirectResponseParser
{
    public function parseExpenses($response)
    {
        // Парсим TSV или JSON ответ и возвращаем данные
        // Реализация зависит от формата ответа
        dd('parseExpenses');
    }

    public function parseAccountBalance($response)
    {
        // Извлекаем баланс из ответа
        dd('parseAccountBalance');
    }

    public function parseReportData($response)
    {
        // Парсим данные отчета
        dd('parseReportData');
    }
}
