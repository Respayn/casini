<?php

namespace App\Livewire\SystemSettings;

use App\Data\ProjectData;
use App\Models\Project;
use App\Services\ClientService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ClientAndProjects extends Component
{
    private ClientService $clientService;

    public Collection $clients;

    public function boot(
        ClientService $clientService,
    )
    {
        $this->clientService = $clientService;
    }

    public function mount()
    {
        $this->clients = $this->clientService->getClients(['projects']);
    }

    public function createClient()
    {
        // TODO: Логика для создания клиента
        dump('hello');
    }

    public function createProject()
    {
        // TODO: Логика для создания клиенто-проекта
        dump('hello');
    }
}
