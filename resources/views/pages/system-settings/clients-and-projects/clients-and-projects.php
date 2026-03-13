<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Clients\GetClientsWithProjects\GetClientsWithProjectsQuery;
use Src\Application\Clients\GetClientsWithProjects\GetClientsWithProjectsQueryHandler;

new
    #[Layout('layouts::system-settings')]
    #[Title('Клиенты и Клиенто-проекты')]
    class extends Component
    {
        #[Computed]
        public function clients()
        {
            return app(GetClientsWithProjectsQueryHandler::class)
                ->handle(new GetClientsWithProjectsQuery());
        }
    };
