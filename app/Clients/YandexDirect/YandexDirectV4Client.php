<?php

namespace App\Clients\YandexDirect;

use App\Contracts\YandexDirectClientInterface;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class YandexDirectV4Client implements YandexDirectClientInterface
{
    protected Client $client;
    protected string $baseUrl;

    public function __construct(
        protected string $token,
        protected string $clientLogin,
        protected bool $sandboxMode = false
    ) {
        $this->baseUrl = $sandboxMode
            ? config('services.yandex_direct.v4_sandbox_url')
            : config('services.yandex_direct.v4_api_url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Client-Login' => $this->clientLogin,
                'Accept-Language' => 'ru',
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function getAccountBalance(): float
    {
        $payload = [
            'method' => 'AccountManagement',
            'param' => [
                'Action' => 'Get',
                'SelectionCriteria' => [
                    'Logins' => [$this->clientLogin]
                ]
            ],
            'token' => $this->token
        ];

        try {
            $response = $this->client->post('', [
                'json' => $payload,
                'http_errors' => false
            ]);

            return $this->processBalanceResponse($response);

        } catch (GuzzleException $e) {
            throw new \RuntimeException('API v4 request failed: '.$e->getMessage());
        }
    }

    private function processBalanceResponse(ResponseInterface $response): float
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode !== 200) {
            throw new \RuntimeException("API v4 error [{$statusCode}]: {$body}");
        }

        $data = json_decode($body, true);

        if (!isset($data['data']['Accounts'][0]['Amount'])) {
            throw new \RuntimeException('Invalid balance response structure');
        }

        return (float)$data['data']['Accounts'][0]['Amount'];
    }

    public function requestReport(array $params): array
    {
        // TODO: Implement requestReport() method.
    }

    public function request(string $method, string $endpoint, array $params = [], array $headers = []): array
    {
        // TODO: Implement request() method.
    }

    public function getCampaigns(array $fields = ['Id', 'Name']): array
    {
        // TODO: Implement getCampaigns() method.
    }

    public function getCampaignStatistics(int $campaignId, Carbon $startDate, Carbon $endDate): array
    {
        // TODO: Implement getCampaignStatistics() method.
    }
}
