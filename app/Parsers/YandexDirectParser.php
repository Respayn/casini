<?php

namespace App\Parsers;

class YandexDirectParser
{
    /**
     * Парсинг TSV строки в массив.
     *
     * @param string $tsvString TSV строка
     * @return array Спаршенный массив данных
     */
    public function parseTsv(string $tsvString): array
    {
        $lines = explode("\n", trim($tsvString));
        $data = [];

        if (empty($lines)) {
            return $data;
        }

        $headers = explode("\t", array_shift($lines)); // Получаем заголовки столбцов

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue; // Пропускаем пустые строки
            }
            $row = explode("\t", $line);
            $data[] = array_combine($headers, $row);
        }

        return $data;
    }
}
