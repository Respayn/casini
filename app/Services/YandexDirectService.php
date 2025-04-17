<?php

namespace App\Services;

use App\Clients\YandexDirect\YandexDirectClient;
use App\Data\YandexDirect\CampaignDTO;
use App\Data\YandexDirect\CampaignStatisticsDTO;
use App\Data\YandexDirect\PerformanceReportDTO;
use App\Exceptions\YandexDirectApiException;
use App\Parsers\YandexDirectReportParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class YandexDirectService
{
    public function __construct(
        private readonly YandexDirectClient $client,
        private readonly YandexDirectReportParser $parser
    ) {}

    /**
     * Получить список рекламных кампаний
     *
     * @return Collection<CampaignDTO>
     * @throws YandexDirectApiException
     */
    public function getCampaigns(): Collection
    {
        try {
            $response = $this->client->request('POST', 'campaigns', [
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
            return $this->client->getAccountBalance();
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
            $reportData = $this->client->requestReport([
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

    /**
     * Получить статистику по кампании
     *
     * @return Collection<CampaignStatisticsDTO>
     * @throws YandexDirectApiException
     */
    public function getCampaignStatistics(
        int $campaignId,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $this->validateDateRange($startDate, $endDate);

        try {
            $response = $this->client->request('POST', 'reports', [
                'params' => $this->buildReportParams(
                    'CAMPAIGN_PERFORMANCE_REPORT',
                    $startDate,
                    $endDate,
                    ['Date', 'Clicks', 'Cost'],
                    [
                        'Page' => [
                            'Limit' => 1000
                        ]
                    ]
                )
            ]);

            return $this->parser->parseCampaignStatistics($response['data'] ?? []);

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'campaignId' => $campaignId,
                'period' => $this->formatDateRange($startDate, $endDate)
            ]);
            throw new YandexDirectApiException('Campaign statistics failed', 0, $e);
        }
    }

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
     * Форматирование периода для логов
     */
    private function formatDateRange(Carbon $start, Carbon $end): string
    {
        return $start->toDateString().' - '.$end->toDateString();
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
