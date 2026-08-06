<?php

use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Support\ClientsAndProjectsPermissions;
use Illuminate\Support\Facades\Auth;
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
        use WithSidebarProjectFilter;

        #[Computed]
        public function clients()
        {
            return app(GetClientsWithProjectsQueryHandler::class)
                ->handle(new GetClientsWithProjectsQuery(
                    auth()->id(),
                    $this->sidebarProjectId,
                ));
        }

        #[Computed]
        public function canEditClientsAndProjects(): bool
        {
            return ClientsAndProjectsPermissions::userCanEdit(Auth::user());
        }

        protected function afterSidebarProjectFilterChanged(): void
        {
            unset($this->clients);
        }
    };
