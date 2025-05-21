<?php

namespace App\Clients\YandexMetrika;

use App\Contracts\YandexMetrikaClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Log;

class YandexMetrikaClient implements YandexMetrikaClientInterface
{
    protected Client $client;
    protected string $baseUrl;

    public function __construct(
        protected string $token,
        public string $clientLogin,
        public ?int $counterId = null,
    ) {
        $this->baseUrl = config('services.yandex_metrika.api_url');

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
    }

    public function request(
        string $method,
        string $endpoint,
        array $params = []
    ): array {
        try {
            $response = $this->client->request($method, $endpoint, [
                'query' => array_merge(['ids' => $this->counterId], $params)
            ]);

            return $this->processResponse($response);

        } catch (GuzzleException $e) {
            $this->handleException($e, $endpoint, $params);
            throw new \Exception('API request failed');
        }
    }

    private function processResponse(Response $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if ($statusCode !== 200) {
            $this->handleErrorResponse($statusCode, $data, $body);
        }

        return $data;
    }

    private function handleErrorResponse(int $code, $data, string $body): void
    {
        $errorMessage = match ($code) {
            400 => 'Invalid request parameters',
            403 => 'Access denied',
            404 => 'Resource not found',
            429 => 'Too many requests',
            default => "Unexpected error: $code"
        };

        Log::error("Yandex Metrika API Error: $errorMessage", [
            'code' => $code,
            'response' => $data,
            'raw_body' => $body
        ]);

        throw new \Exception($errorMessage, $code);
    }

    private function handleException(
        GuzzleException $e,
        string $endpoint,
        array $params
    ): void {
        Log::error("Yandex Metrika API Exception: {$e->getMessage()}", [
            'endpoint' => $endpoint,
            'params' => $params,
        ]);
    }

    public function getGoals(): array
    {
        return $this->request('GET', "management/v1/counter/{$this->counterId}/goals");
    }

    public function getVisitsReport(array $params): array
    {
        return $this->request('GET', 'stat/v1/data', array_merge($params, ['id' => $this->counterId]));
    }

    public function getGoalAchievements(int $goalId, array $params = []): array
    {
        return $this->request('GET', "stat/v1/data/bygoal", array_merge([
            'goal_id' => $goalId
        ], $params));
    }
}
