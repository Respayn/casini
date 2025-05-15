<?php

namespace App\Services;

use App\Clients\YandexDirect\YandexDirectClient;
use App\Data\YandexDirect\CampaignDTO;
use App\Data\YandexDirect\CampaignStatisticsDTO;
use App\Data\YandexDirect\MonthlyExpenseDTO;
use App\Data\YandexDirect\PerformanceReportDTO;
use App\Exceptions\YandexDirectApiException;
use App\Factories\YandexDirectClientFactory;
use App\Parsers\YandexDirectReportParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class YandexDirectService
{
    private readonly YandexDirectClient $client;

    public function __construct(
        private readonly YandexDirectReportParser $parser,
        private readonly YandexDirectClientFactory $clientFactory
    ) {}

    public function setupClient($token, $clientLogin)
    {
        $this->client = $this->clientFactory->create($token, $clientLogin);
    }

    private function getClient(): YandexDirectClient
    {
        if ($this->client === null) {
            throw new \Exception('YandexDirectClient is not initialized. Call setupClient() first.');
        }
        return $this->client;
    }

    /**
     * Получить список рекламных кампаний
     *
     * @return Collection<CampaignDTO>
     * @throws YandexDirectApiException
     */
    public function getCampaigns(): Collection
    {
        try {
            $response = $this->getClient()->request('POST', 'campaigns', [
                'method' => 'get',
                'params' => [
                    'SelectionCriteria' => (object)[],
                    'FieldNames' => ['Id', 'Name', 'Status']
                ]
            ]);

            return collect($response['data']['result']['Campaigns'] ?? [])
                ->map(fn($campaign) => new CampaignDTO(
                    $campaign['Id'],
                    $campaign['Name'],
                    $campaign['Status']
                ));

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e);
            throw new YandexDirectApiException('Failed to get campaigns', 0, $e);
        }
    }

    /**
     * Получить текущий баланс аккаунта
     *
     * @throws YandexDirectApiException
     */
    public function getAccountBalance(): float
    {
        try {
            return $this->getClient()->getAccountBalance();
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e);
            throw new YandexDirectApiException('Balance check failed', 0, $e);
        }
    }

    /**
     * Сформировать отчет о производительности
     *
     * @return Collection<PerformanceReportDTO>
     * @throws YandexDirectApiException
     */
    public function getPerformanceReport(
        Carbon $startDate,
        Carbon $endDate,
        array $metrics = ['Impressions', 'Clicks', 'Cost']
    ): Collection {
        $this->validateDateRange($startDate, $endDate);

        try {
            $reportData = $this->getClient()->requestReport([
                'params' => $this->buildReportParams(
                    'ACCOUNT_PERFORMANCE_REPORT',
                    $startDate,
                    $endDate,
                    $metrics
                )
            ]);
            return $this->parser->parsePerformanceReport($reportData);

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]);
            throw new YandexDirectApiException('Report generation failed', 0, $e);
        }
    }

//    /**
//     * Получить статистику по кампании
//     *
//     * @return Collection<CampaignStatisticsDTO>
//     * @throws YandexDirectApiException
//     */
//    public function getCampaignStatistics(
//        int $campaignId,
//        Carbon $startDate,
//        Carbon $endDate
//    ): Collection {
//        $this->validateDateRange($startDate, $endDate);
//
//        try {
//            $response = $this->getClient()->request('POST', 'reports', [
//                'params' => $this->buildReportParams(
//                    'CAMPAIGN_PERFORMANCE_REPORT',
//                    $startDate,
//                    $endDate,
//                    ['Date', 'Clicks', 'Cost'],
//                    [
//                        'Page' => [
//                            'Limit' => 1000
//                        ]
//                    ]
//                )
//            ]);
//
//            return $response['data'];
//
//        } catch (\Exception $e) {
//            $this->logError(__METHOD__, $e, [
//                'campaignId' => $campaignId,
//                'period' => $this->formatDateRange($startDate, $endDate)
//            ]);
//            throw new YandexDirectApiException('Campaign statistics failed', 0, $e);
//        }
//    }

    /**
     * Сформировать параметры отчета
     */
    private function buildReportParams(
        string $reportType,
        Carbon $startDate,
        Carbon $endDate,
        array $fields,
        array $additionalCriteria = []
    ): array {
        $baseParams = [
            'SelectionCriteria' => (object)[
                'DateFrom' => $startDate->format('Y-m-d'),
                'DateTo' => $endDate->format('Y-m-d')
            ],
            'FieldNames' => $fields,
            'ReportName' => 'Report_'.time(),
            'ReportType' => $reportType,
            'DateRangeType' => 'CUSTOM_DATE',
            'Format' => 'TSV',
            'IncludeVAT' => 'YES',
            'IncludeDiscount' => 'NO'
        ];

        return array_merge($baseParams, $additionalCriteria);
    }

    /**
     * Валидация временного диапазона
     */
    private function validateDateRange(Carbon $start, Carbon $end): void
    {
        if ($start->isAfter($end)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        if ($end->diffInDays($start) > 365) {
            throw new \InvalidArgumentException('Maximum date range exceeded (365 days)');
        }
    }

    /**
     * Получить общие расходы по проекту за период
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     * @throws YandexDirectApiException
     */
    public function getProjectExpenses(Carbon $startDate, Carbon $endDate): float
    {
        $report = $this->getPerformanceReport(
            $startDate,
            $endDate,
            ['Cost', 'Impressions', 'Clicks']
        );

        return $report->sum('cost');
    }

    /**
     * Получить расходы по проекту с группировкой по месяцам
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection<MonthlyExpenseDTO>
     * @throws YandexDirectApiException
     */
    public function getProjectExpensesByMonth(Carbon $startDate, Carbon $endDate): Collection
    {
        $this->validateDateRange($startDate, $endDate);

        try {
            $reportData = $this->getClient()->requestReport([
                'params' => $this->buildReportParams(
                    'ACCOUNT_PERFORMANCE_REPORT',
                    $startDate,
                    $endDate,
                    ['Date', 'Cost']
                )
            ]);

            return $this->groupByMonth($reportData);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]);
            throw new YandexDirectApiException('Failed to get monthly expenses', 0, $e);
        }
    }

    /**
     * Группировка данных по месяцам
     */
    private function groupByMonth(array $reportData): Collection
    {
        return collect($reportData)
            ->groupBy(fn($item) => Carbon::parse($item['Date'])->format('Y-m'))
            ->map(function ($items, $month) {
                return new MonthlyExpenseDTO(
                    Carbon::createFromFormat('Y-m', $month),
                    $items->sum(fn($i) => (float)$i['Cost'])
                );
            })
            ->values();
    }

    /**
     * Унифицированное логирование ошибок
     */
    private function logError(string $method, \Throwable $e, array $context = []): void
    {
        Log::channel('yandex_direct')->error("[$method] {$e->getMessage()}", [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);
    }
}
