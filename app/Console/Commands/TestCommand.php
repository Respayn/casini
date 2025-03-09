<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Department;
use App\Repositories\DepartmentRepository;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Команда для вариативного тестирования методов';

    public function handle(DepartmentRepository $departmentRepository)
    {
        dd($departmentRepository->all());
    }
}
