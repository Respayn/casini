<?php

namespace App\Clients\Callibri;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class CallibriClient
{
    private Client $client;

    public function __construct(
        private string $email,
        private string $token,
        private string $baseUrl
    ) {
        $stack = HandlerStack::create();
        $stack->push($this->retryMiddleware());

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'handler' => $stack,
            'query' => [
                'user_email' => $this->email,
                'user_token' => $this->token
            ]
        ]);
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        $options = array_merge_recursive($options, ['query' => ['user_email' => $this->email, 'user_token' => $this->token]]);
        $response = $this->client->request($method, $uri, $options);
        return json_decode($response->getBody(), true);
    }

    private function retryMiddleware(): callable
    {
        return Middleware::retry(
            function(
                $retries,
                \GuzzleHttp\Psr7\Request $request,
                ResponseInterface $response = null
            ) {
                if ($response && $response->getStatusCode() === 429) {
                    $retryAfter = $response->getHeader('Retry-After')[0] ?? 1;
                    sleep((int)$retryAfter);
                    return true;
                }
                return false;
            },
            function($retries) {
                return $retries < 3;
            }
        );
    }
}
