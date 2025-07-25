<?php

namespace App\Console\Commands;

use App\Services\CallibriService;
use App\Services\RoleService;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Команда для вариативного тестирования методов';

    protected CallibriService $service;

    public function __construct(CallibriService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(RoleService $service)
    {
        $enums = $service->getRoleOptions();
        dd($enums);
    }
}
