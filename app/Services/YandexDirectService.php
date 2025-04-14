<?php

namespace App\Services;

use App\Clients\YandexDirectApiClient;
use App\Parsers\YandexDirectResponseParser;
use Exception;

class YandexDirectService
{

    public function __construct(
        protected YandexDirectApiClient $apiClient,
        protected YandexDirectResponseParser $parser
    ) {
    }

    /**
     * Получение расходов по проекту за интервал времени.
     *
     * @param $dateFrom
     * @param $dateTo
     * @return mixed
     * @throws Exception
     */
    public function getExpenses($dateFrom, $dateTo): mixed
    {
        $params = [
            'SelectionCriteria' => [
                'DateFrom' => $dateFrom,
                'DateTo' => $dateTo,
            ],
            'FieldNames' => ['Cost', 'Date'],
            'ReportName' => 'ExpensesReport',
            'ReportType' => 'ACCOUNT_PERFORMANCE_REPORT',
            'DateRangeType' => 'CUSTOM_DATE',
            'Format' => 'TSV',
            'IncludeVAT' => 'YES',
            'IncludeDiscount' => 'YES',
        ];

        $response = $this->apiClient->sendRequest('reports', 'get', $params);

        return $this->parser->parseExpenses($response);
    }

    /**
     * Получение расходов по проекту за интервал времени с группировкой.
     *
     * @param $dateFrom
     * @param $dateTo
     * @param $groupBy
     * @return void
     */
    public function getExpensesGrouped($dateFrom, $dateTo, $groupBy = 'MONTH')
    {
        // Дополнительно добавить группировку
        // Реализация аналогична предыдущему методу, но добавляем параметры группировки
    }

    /**
     * Получение баланса аккаунта.
     *
     * @return mixed
     */
    public function getAccountBalance()
    {
        $response = $this->apiClient->sendRequest('Live', 'get', [
            'method' => 'AccountManagement',
            'param' => ['Action' => 'Get'],
        ]);

        return $this->parser->parseAccountBalance($response);
    }

    /**
     * Получение данных для формирования отчетов.
     *
     * @param $dateFrom
     * @param $dateTo
     * @return mixed
     */
    public function getReportData($dateFrom, $dateTo)
    {
        $params = [
            'SelectionCriteria' => [
                'DateFrom' => $dateFrom,
                'DateTo' => $dateTo,
            ],
            'FieldNames' => ['Impressions', 'Clicks', 'Cost', 'Ctr', 'Date'],
            'ReportName' => 'PerformanceReport',
            'ReportType' => 'ACCOUNT_PERFORMANCE_REPORT',
            'DateRangeType' => 'CUSTOM_DATE',
            'Format' => 'TSV',
            'IncludeVAT' => 'YES',
            'IncludeDiscount' => 'YES',
        ];

        $response = $this->apiClient->sendRequest('reports', 'get', $params);

        return $this->parser->parseReportData($response);
    }
}
