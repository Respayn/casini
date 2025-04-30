<?php

namespace App\Services;

use App\Clients\Callibri\CallibriClient;
use App\Data\Callibri\SiteData;
use App\Exceptions\CallibriApiException;
use App\Factories\CallibriClientFactory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CallibriService
{
    private CallibriClient $client;

    public function __construct(
        private CallibriClientFactory $clientFactory
    ) {}

    public function setupClient(string $email, string $token): void
    {
        $this->client = $this->clientFactory->create($email, $token);
    }

    public function getSites(): Collection
    {
        try {
            $response = $this->client->request('GET', 'get_sites');

            return collect($response['sites'])->map(
                fn(array $item) => SiteData::from($item)
            );

        } catch (\Exception $e) {
            throw new CallibriApiException('Failed to get sites', 0, $e);
        }
    }
}
