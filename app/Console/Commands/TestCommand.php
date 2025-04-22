<?php

namespace App\Console\Commands;

use App\Exceptions\YandexDirectApiException;
use App\Services\YandexDirectService;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Команда для вариативного тестирования методов';

    protected YandexDirectService $service;

    public function __construct(YandexDirectService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {

    }
}
