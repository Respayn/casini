<?php

use App\Services\ClientService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::system-settings')]
    #[Title('Клиенты и Клиенто-проекты')]
    class extends Component
    {
        private ClientService $clientService;

        public function boot(ClientService $clientService)
        {
            $this->clientService = $clientService;
        }

        #[Computed]
        public function clients()
        {
            return $this->clientService->getClients(['projects']);
        }
    };
