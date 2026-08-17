<?php

namespace App\Livewire;

use App\Data\Sidebar\EmployeeData;
use App\Services\SidebarService;
use App\Support\SidebarProjectContext;
use Livewire\Attributes\On;
use Livewire\Component;
use Str;

class Sidebar extends Component
{
    /** @var array<int, EmployeeData> */
    public array $employees;

    public array $sortOptions = [];

    public ?string $sortBy = 'manager';

    public string $searchQuery = '';

    public ?int $selectedProjectId = null;

    private SidebarService $sidebarService;

    private SidebarProjectContext $projectContext;

    public function boot(SidebarService $sidebarService, SidebarProjectContext $projectContext)
    {
        $this->sidebarService = $sidebarService;
        $this->projectContext = $projectContext;
    }

    public function mount()
    {
        $this->sortOptions = $this->sidebarService->getRoleOptions();
        $this->selectedProjectId = $this->projectContext->get();
        $this->getEmployees();
    }

    public function updatedSortBy()
    {
        $this->getEmployees();
    }

    public function updatedSearchQuery()
    {
        $this->getEmployees();
    }

    public function selectProject(int $projectId): void
    {
        if ($this->selectedProjectId === $projectId) {
            $this->resetSelectedProject();

            return;
        }

        if (! $this->projectContext->set($projectId)) {
            return;
        }

        $this->selectedProjectId = $projectId;
        $this->dispatch('sidebar-project-selected', projectId: $projectId);
    }

    public function resetSelectedProject(): void
    {
        $this->projectContext->clear();
        $this->selectedProjectId = null;
        $this->dispatch('sidebar-project-cleared');
    }

    #[On('sidebar-project-cleared')]
    public function syncClearedSelection(): void
    {
        $this->selectedProjectId = null;
    }

    private function getEmployees()
    {
        $this->employees = $this->sidebarService->getEmployees($this->sortBy, $this->searchQuery);

        foreach ($this->employees as &$employee) {
            foreach ($employee->clients as &$client) {
                foreach ($client->projects as $project) {
                    if (Str::contains(Str::lower($project->name), Str::lower($this->searchQuery))) {
                        $employee->open = true;
                        $client->open = true;
                    }
                }
                if (Str::contains(Str::lower($client->name), Str::lower($this->searchQuery))) {
                    $employee->open = true;
                }
            }
        }
        unset($employee, $client);
    }

    public function render()
    {
        return view('livewire.sidebar');
    }
}
