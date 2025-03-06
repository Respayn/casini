<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Команда для вариативного тестирования методов';

    public function handle()
    {
        $clients = Client::query()->get();
        dd($clients[0]);
    }
}
