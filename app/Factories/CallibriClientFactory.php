<?php

namespace App\Factories;

use App\Clients\Callibri\CallibriClient;

class CallibriClientFactory
{
    public function create(string $email, string $token): CallibriClient
    {
        return new CallibriClient(
            $email,
            $token,
            config('services.callibri.api_url')
        );
    }
}
