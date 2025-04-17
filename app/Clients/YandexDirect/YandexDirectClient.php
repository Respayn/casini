<?php

namespace App\Clients\YandexDirect;

use App\Contracts\YandexDirectClientInterface;
use App\Exceptions\YandexDirectApiException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class YandexDirectClient implements YandexDirectClientInterface
{
    protected Client $client;
    protected string $baseUrl;
    private YandexDirectV4Client $v4Client;

    public function __construct(
        protected string $token,
        public string $clientLogin,
        protected bool $sandboxMode = false,
    ) {

        $this->baseUrl = $this->sandboxMode
            ? config('services.yandex_direct.sandbox_api_url')
            : config('services.yandex_direct.api_url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Client-Login' => $this->clientLogin,
                'Accept-Language' => 'ru',
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'allow_redirects' => false,
            'http_errors' => false,
        ]);

        $this->v4Client = new YandexDirectV4Client(
            $token,
            $clientLogin,
            $sandboxMode
        );
    }

    /**
     * Основной метод для отправки запросов к API
     */
    public function request(
        string $method,
        string $endpoint,
        array $params = [],
        array $headers = []
    ): array {
        $options = $this->prepareRequestOptions($method, $params, $headers);

        try {
            $response = $this->client->request($method, $this->baseUrl . $endpoint, $options);
            return $this->processResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e, $endpoint, $params);
            throw new \RuntimeException('API request failed');
        }
    }

    /**
     * Специальный метод для асинхронных отчетов
     */
    public function requestReport(array $params): array
    {
        $attempt = 0;
        $maxAttempts = 10;

        do {
            $response = $this->request('POST', 'reports', $params, [
                'processingMode' => 'auto',
                'returnMoneyInMicros' => 'false',
                'skipReportHeader' => 'true',
                'skipReportSummary' => 'true',
            ]);

            if ($response['status'] === 'done') {
                // Добавляем парсинг TSV
                return $this->parseTsvResponse($response['data']);
            }

            sleep($response['retryIn'] ?? 10);
        } while ($attempt++ < $maxAttempts);

        throw new \RuntimeException('Report generation timeout');
    }

    /**
     * Парсинг TSV ответа в массив
     */
    private function parseTsvResponse(string $tsvData): array
    {
        $lines = explode("\n", trim($tsvData));
        if (count($lines) < 2) {
            return [];
        }

        $headers = explode("\t", array_shift($lines));
        $result = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $values = explode("\t", $line);
            $result[] = array_combine($headers, $values);
        }

        return $result;
    }

    private function prepareRequestOptions(
        string $method,
        array $params,
        array $headers
    ): array {
        $options = ['headers' => $headers];

        if ($method === 'GET') {
            $options['query'] = $params;
        } else {
            $options['json'] = $params;
        }

        return $options;
    }

    private function processResponse(Response $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true) ?? $body;

        if ($statusCode === 200) {
            return ['status' => 'done', 'data' => $data];
        }

        if ($statusCode === 201 || $statusCode === 202) {
            return [
                'status' => 'pending',
                'retryIn' => $this->getRetryInterval($response),
            ];
        }

        $this->handleErrorResponse($statusCode, $data, $body);
    }

    private function getRetryInterval(Response $response): int
    {
        return (int)current($response->getHeader('RetryIn')) ?: 10;
    }

    private function handleErrorResponse(int $code, $data, string $body): void
    {
        $errorMessage = match ($code) {
            400 => 'Invalid request parameters',
            401 => 'Authentication failed',
            403 => 'Access denied',
            500 => 'Internal server error',
            502 => 'Server unavailable',
            default => "Unexpected error: {$code}",
        };

        Log::error("Yandex Direct API Error: {$errorMessage}", [
            'code' => $code,
            'response' => $data,
            'raw_body' => $body,
        ]);

        throw new YandexDirectApiException($errorMessage, 400);
    }

    private function handleException(
        GuzzleException $e,
        string $endpoint,
        array $params
    ): void {
        Log::error("Yandex Direct API Exception: {$e->getMessage()}", [
            'endpoint' => $endpoint,
            'params' => $params,
        ]);
    }

    /**
     * Получение баланса через API v4
     */
    public function getAccountBalance(): float
    {
        return $this->v4Client->getAccountBalance();
    }

    /**
     * Получить список кампаний
     */
    public function getCampaigns(array $fields = ['Id', 'Name']): array
    {
        try {
            $response = $this->request('POST', 'campaigns', [
                'method' => 'get',
                'params' => [
                    'SelectionCriteria' => [],
                    'FieldNames' => $fields,
                ]
            ]);

            return $response['result']['Campaigns'] ?? [];

        } catch (GuzzleException $e) {
            throw new YandexDirectApiException('Failed to get campaigns', 0, $e);
        }
    }

    /**
     * Получить статистику по кампании
     */
    public function getCampaignStatistics(
        int $campaignId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        try {
            $response = $this->request('POST', 'reports', [
                'params' => [
                    'SelectionCriteria' => [
                        'CampaignIds' => [$campaignId],
                        'DateFrom' => $startDate->format('Y-m-d'),
                        'DateTo' => $endDate->format('Y-m-d'),
                    ],
                    'FieldNames' => ['Date', 'Clicks', 'Cost'],
                    'ReportType' => 'CAMPAIGN_PERFORMANCE_REPORT',
                    'Page' => ['Limit' => 1000],
                ]
            ]);

            return $response['result'] ?? [];

        } catch (GuzzleException $e) {
            throw new YandexDirectApiException('Campaign statistics failed', 0, $e);
        }
    }
}
