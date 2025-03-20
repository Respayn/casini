<?php

namespace App\Livewire\SystemSettings;

use App\Data\ClientData;
use App\Livewire\Forms\SystemSettings\ClientAndProjects\ClientForm;
use App\Services\ClientService;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.system-settings')]
class ClientAndProjects extends Component
{
    public ClientForm $clientForm;
    private ClientService $clientService;
    private UserService $userService;

    public Collection $clients;
    public Collection $managers;

    public ?int $activeClientIndex = null;

    public function boot(
        ClientService $clientService,
        UserService $userService,
    )
    {
        $this->clientService = $clientService;
        $this->userService = $userService;
    }

    public function mount()
    {
        $this->clients = $this->clientService->getClients(['projects']);
        $this->managers = $this->userService->getManagers();
    }

    public function initClientForm(int $clientIndex = null)
    {
        $this->activeClientIndex = $clientIndex;
        if ($clientIndex === null) {
            $this->clientForm->reset();
        } else {
            $client = $this->clients[$clientIndex];
            $this->clientForm->from($client);
        }

        $this->dispatch('modal-show', name: 'client-modal');
    }

    public function saveClient()
    {
        $this->clientForm->validate();

        DB::beginTransaction();

        try {
            $clientData = new ClientData(
                id: $this->clientForm->id,
                name: $this->clientForm->name,
                inn: $this->clientForm->inn,
                initial_balance: $this->clientForm->initial_balance,
                manager_id: $this->clientForm->manager,
            );

            $clientData = $this->clientService->updateOrCreateClient($clientData);

            DB::commit();

            if (!empty($this->clientForm->id)) {
                $this->clients[$this->activeClientIndex] = $clientData;
                return $this->dispatch('modal-hide', name: 'client-modal');
            } else {
                return redirect()->route('system-settings.clients-and-projects');
            }
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
